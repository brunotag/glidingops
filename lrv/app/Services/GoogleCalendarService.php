<?php

namespace App\Services;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Auth_AssertionCredentials;

class GoogleCalendarService
{
    protected $service;
    protected $calendarId;

    public function __construct($calendarId = null, $keyPath = null)
    {
        if ($calendarId === null || $keyPath === null) {
            $gcalConfig = require __DIR__ . '/../../../config/google-calendar.php';
            $calendarId = $gcalConfig['calendar_id'];
            $keyPath = $gcalConfig['service_account_key'];
        }

        $this->calendarId = $calendarId;
        if (!$keyPath || !file_exists($keyPath)) {
            throw new \RuntimeException('Google Calendar service account key not found at: ' . $keyPath);
        }

        $keyData = json_decode(file_get_contents($keyPath), true);
        if (!$keyData || !isset($keyData['client_email']) || !isset($keyData['private_key'])) {
            throw new \RuntimeException('Invalid service account key file');
        }

        $client = new Google_Client();
        $creds = new Google_Auth_AssertionCredentials(
            $keyData['client_email'],
            [Google_Service_Calendar::CALENDAR],
            $keyData['private_key'],
            'notasecret'
        );
        $client->setAssertionCredentials($creds);

        $this->service = new Google_Service_Calendar($client);
    }

    public function getEventsForDateRange($startDate, $endDate)
    {
        $optParams = [
            'timeMin' => $startDate . 'T00:00:00+12:00',
            'timeMax' => $endDate . 'T23:59:59+12:00',
            'singleEvents' => true,
            'orderBy' => 'startTime',
        ];

        $events = [];
        $pageToken = null;
        do {
            if ($pageToken) {
                $optParams['pageToken'] = $pageToken;
            }
            $result = $this->service->events->listEvents($this->calendarId, $optParams);
            foreach ($result->getItems() as $event) {
                $events[] = [
                    'id' => $event->getId(),
                    'summary' => $event->getSummary(),
                    'description' => $event->getDescription(),
                    'start' => $event->getStart()->getDateTime(),
                    'end' => $event->getEnd()->getDateTime(),
                    'htmlLink' => $event->getHtmlLink(),
                ];
            }
            $pageToken = $result->getNextPageToken();
        } while ($pageToken);

        return $events;
    }

    public function getNextSequence($date)
    {
        $events = $this->getEventsForDateRange($date, $date);
        return count($events) + 1;
    }

    public function createEvent($date, $sequence, $summary, $description)
    {
        $timezone = 'Pacific/Auckland';
        $baseHour = 9;
        $startMinute = ($sequence - 1) * 1;

        $startTime = sprintf('%sT%02d:%02d:00', $date, $baseHour, $startMinute);

        $start = new \Google_Service_Calendar_EventDateTime();
        $start->setDateTime($startTime);
        $start->setTimeZone($timezone);

        $endTime = date('Y-m-d\TH:i:s', strtotime($startTime . ' +1 minute'));
        $end = new \Google_Service_Calendar_EventDateTime();
        $end->setDateTime($endTime);
        $end->setTimeZone($timezone);

        $event = new Google_Service_Calendar_Event([
            'summary' => $summary,
            'description' => $description,
            'start' => $start,
            'end' => $end,
        ]);

        $created = $this->service->events->insert($this->calendarId, $event);
        return $created->getId();
    }

    public function updateEvent($eventId, $summary, $description)
    {
        $event = $this->service->events->get($this->calendarId, $eventId);
        $event->setSummary($summary);
        $event->setDescription($description);
        $this->service->events->update($this->calendarId, $eventId, $event);
    }

    public function deleteEvent($eventId)
    {
        $this->service->events->delete($this->calendarId, $eventId);
    }
}
