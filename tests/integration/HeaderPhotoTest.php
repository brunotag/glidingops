<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class HeaderPhotoTest extends TestCase
{
    private static Client $client;
    private static array $htmlCache = [];

    public static function setUpBeforeClass(): void
    {
        self::$client = loginClient();
    }

    private function getPage(string $url): string
    {
        if (!isset(self::$htmlCache[$url])) {
            $resp = self::$client->get($url);
            self::$htmlCache[$url] = (string)$resp->getBody();
        }
        return self::$htmlCache[$url];
    }

    public function testHeaderOnHome(): void
    {
        $html = $this->getPage('/home');
        $this->assertStringContainsString('/img/members/', $html);
        $this->assertStringContainsString('head-user', $html);
    }

    public function testHeaderOnAllMembers(): void
    {
        $html = $this->getPage('/AllMembers');
        $this->assertStringContainsString('/img/members/', $html);
        $this->assertStringContainsString('head-user', $html);
    }

    public function testHeaderOnMyFlights(): void
    {
        $html = $this->getPage('/MyFlights');
        $this->assertStringContainsString('/img/members/', $html);
        $this->assertStringContainsString('head-user', $html);
    }

    public function testHeaderNoprofileFallback(): void
    {
        $html = $this->getPage('/home');
        $this->assertStringContainsString("onerror=\"this.src='/img/noprofile.png'\"", $html);
    }

    public function testNoprofileImageLoads(): void
    {
        $resp = self::$client->get('/img/noprofile.png');
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertGreaterThan(1000, strlen((string)$resp->getBody()));
    }

    public function testHeaderPhotoSrcSetsMemberId(): void
    {
        $html = $this->getPage('/home');
        $this->assertMatchesRegularExpression('#/img/members/\d+\.jpg#', $html);
    }
}
