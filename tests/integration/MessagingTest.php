<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class MessagingTest extends TestCase
{
    private static Client $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = loginAsPersona('daily-ops');
    }

    public static function tearDownAfterClass(): void
    {
        restoreAllPersonas();
    }

    /**
     * Decode JSON response, stripping any leading BOM.
     */
    private static function jsonDecode(string $raw): ?array
    {
        // Strip UTF-8 BOM if present
        if (strlen($raw) >= 3 && ord($raw[0]) === 0xEF && ord($raw[1]) === 0xBB && ord($raw[2]) === 0xBF) {
            $raw = substr($raw, 3);
        }
        return json_decode($raw, true);
    }

    /**
     * Send a test message and return the response array.
     */
    private function sendMessage(string $uid, string $email): array
    {
        $resp = self::$client->post('/MessagingPage.php', [
            'allow_redirects' => false,
            'form_params' => [
                'action' => 'send',
                'message' => 'MsgTest ' . $uid,
                'subject' => 'Subject ' . $uid,
                'fakeTwitter' => 0,
                'recipients' => json_encode([
                    ['email' => $email, 'name' => 'Recipient'],
                ]),
            ],
        ]);

        $this->assertEquals(200, $resp->getStatusCode(), 'MessagingPage POST status');
        $body = self::jsonDecode((string)$resp->getBody());
        $this->assertIsArray($body, 'MessagingPage should return valid JSON');
        return $body;
    }

    public function testSendBroadcast(): void
    {
        $uid = uniqueId();
        $body = $this->sendMessage($uid, 'test-' . $uid . '@wwgc.co.nz');

        $this->assertGreaterThan(0, $body['success'], 'Message should be sent to at least 1 recipient');
        $this->assertEmpty($body['failed'], 'No recipients should fail');
    }

    public function testMessageAppearsInMessagesTree(): void
    {
        $uid = uniqueId();
        $this->sendMessage($uid, 'tree-' . $uid . '@wwgc.co.nz');

        $treeResp = self::$client->get('/MessagesTree', ['allow_redirects' => false]);
        $this->assertEquals(200, $treeResp->getStatusCode());
        $treeHtml = (string)$treeResp->getBody();
        $this->assertStringContainsString($uid, $treeHtml, 'Message UID should appear in MessagesTree');
    }

    /** Member-level user cannot access MessagingPage. */
    public function testMemberCannotAccessMessaging(): void
    {
        $client = loginAsPersona('member');

        $resp = $client->get('/MessagingPage', ['allow_redirects' => false]);
        $status = $resp->getStatusCode();
        $body = (string)$resp->getBody();

        $denied = $status === 302 || $status === 403
            || stripos($body, 'Not authorized') !== false;
        $this->assertTrue($denied, 'Member should be denied access to MessagingPage');
    }

    /** Booking-level user cannot access MessagingPage. */
    public function testBookingCannotAccessMessaging(): void
    {
        $client = loginAsPersona('booking');

        $resp = $client->get('/MessagingPage', ['allow_redirects' => false]);
        $status = $resp->getStatusCode();
        $body = (string)$resp->getBody();

        $denied = $status === 302 || $status === 403
            || stripos($body, 'Not authorized') !== false;
        $this->assertTrue($denied, 'Booking should be denied access to MessagingPage');
    }

    /** CFI-level user cannot access MessagingPage (only daily-ops). */
    public function testCfiCannotAccessMessaging(): void
    {
        $client = loginAsPersona('cfi');

        $resp = $client->get('/MessagingPage', ['allow_redirects' => false]);
        $status = $resp->getStatusCode();
        $body = (string)$resp->getBody();

        $denied = $status === 302 || $status === 403
            || stripos($body, 'Not authorized') !== false;
        $this->assertTrue($denied, 'CFI should be denied access to MessagingPage');
    }
}
