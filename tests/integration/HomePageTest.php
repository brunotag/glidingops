<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class HomePageTest extends TestCase
{
    private static Client $client;
    private static string $html;

    public static function setUpBeforeClass(): void
    {
        self::$client = loginClient();
        $resp = self::$client->get('/home');
        self::$html = (string)$resp->getBody();
    }

    public function testPageLoads(): void
    {
        $resp = self::$client->get('/home');
        $this->assertEquals(200, $resp->getStatusCode());
    }

    public function testCssGridPresent(): void
    {
        $this->assertStringContainsString('grid-template-columns', self::$html);
        $this->assertStringContainsString('grid-auto-flow: dense', self::$html);
    }

    public function testWideWidgetHeaders(): void
    {
        $this->assertStringContainsString('Latest Updates', self::$html);
        $this->assertStringContainsString('My Gliding', self::$html);
        $this->assertStringContainsString('Flying / Tracking', self::$html);
    }

    public function testLatestUpdatesHeight(): void
    {
        $this->assertStringContainsString('height:340px', self::$html);
    }

    public function testLatestUpdatesLimit(): void
    {
        $count = substr_count(self::$html, 'class="msg-item');
        $this->assertLessThanOrEqual(4, $count, "Expected at most 4 messages, got $count");
    }

    public function testDataMaintenanceAfterSuperAdmin(): void
    {
        $posSA = strpos(self::$html, 'Super Admin');
        $posDM = strpos(self::$html, 'Data Maintenance');
        $this->assertNotFalse($posSA, 'Super Admin section not found');
        $this->assertNotFalse($posDM, 'Data Maintenance section not found');
        $this->assertGreaterThan($posSA, $posDM, 'Data Maintenance should appear after Super Admin');
    }

    public function testMemberNameGenitive(): void
    {
        $this->assertStringContainsString("'s last 5 flights:", self::$html);
    }

    public function testViewAllYourFlights(): void
    {
        $this->assertStringContainsString('View all your flights', self::$html);
    }

    public function testEditYourDetails(): void
    {
        $this->assertStringContainsString('Edit Your Details', self::$html);
    }
}
