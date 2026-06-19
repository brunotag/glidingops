<?php

use PHPUnit\Framework\TestCase;

class BillingReportTest extends TestCase
{
    public function testPageLoadsAndShowsFlights(): void
    {
        $client = loginClient();

        $resp = $client->post('/BillingReport', [
            'form_params' => [
                'month' => date('m'),
                'year'  => date('Y'),
                'view'  => 'View Report',
            ],
        ]);

        $this->assertEquals(200, $resp->getStatusCode());
        $html = (string)$resp->getBody();

        $this->assertStringContainsString('Treasurer Monthly Report', $html);
        $this->assertStringContainsString('Flight Charges', $html);
    }

    public function testCsvExportDownloads(): void
    {
        $client = loginClient();

        $resp = $client->post('/BillingReport.csv', [
            'form_params' => [
                'month'  => date('m'),
                'year'   => date('Y'),
                'export' => 'Export CSV',
            ],
        ]);

        $this->assertEquals(200, $resp->getStatusCode());
        $contentType = $resp->getHeaderLine('Content-Type');
        $this->assertStringContainsString('text/csv', $contentType);

        $body = (string)$resp->getBody();
        $this->assertStringContainsString('SURNAME', $body);
    }
}
