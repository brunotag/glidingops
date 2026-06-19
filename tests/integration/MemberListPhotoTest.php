<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class MemberListPhotoTest extends TestCase
{
    private static Client $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = loginClient();
    }

    public function testPhotoUrlIsIdBased(): void
    {
        $resp = self::$client->get('/api/members', [
            'query' => ['draw' => 1, 'start' => 0, 'length' => 200],
        ]);

        $this->assertEquals(200, $resp->getStatusCode());
        $data = json_decode((string)$resp->getBody(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('data', $data);
        $this->assertNotEmpty($data['data']);

        foreach ($data['data'] as $member) {
            if ($member['photo_url'] === null) {
                continue; // no photo at all, fine
            }
            $this->assertMatchesRegularExpression(
                '#^/img/members/\d+\.jpg$#',
                $member['photo_url'],
                'Member ' . $member['id'] . ' has non-ID-based photo_url: ' . $member['photo_url']
            );
        }
    }

    public function testNoDisplaynameBasedUrls(): void
    {
        $resp = self::$client->get('/api/members', [
            'query' => ['draw' => 1, 'start' => 0, 'length' => 200],
        ]);

        $data = json_decode((string)$resp->getBody(), true);
        foreach ($data['data'] as $member) {
            $url = $member['photo_url'] ?? '';
            if ($url === '') {
                continue;
            }
            // Filename after last / should be numeric .jpg
            $filename = basename($url);
            $this->assertMatchesRegularExpression(
                '/^\d+\.jpg$/',
                $filename,
                'Member ' . $member['id'] . ' has non-numeric photo filename: ' . $filename
            );
        }
    }

    public function testApiReturnsJson(): void
    {
        $resp = self::$client->get('/api/members', [
            'query' => ['draw' => 1, 'start' => 0, 'length' => 10],
        ]);

        $this->assertEquals(200, $resp->getStatusCode());
        $type = $resp->getHeaderLine('Content-Type');
        $this->assertStringContainsString('application/json', $type);
    }

    public function testKnownMemberPhotoFallback(): void
    {
        $resp = self::$client->get('/api/members', [
            'query' => ['draw' => 1, 'start' => 0, 'length' => 200],
        ]);

        $data = json_decode((string)$resp->getBody(), true);
        $members = array_filter($data['data'], fn($m) => $m['id'] == 1);
        $member = reset($members);

        if (!$member) {
            $this->markTestSkipped('Member with ID 1 not found');
        }

        if ($member['photo_url'] !== null) {
            $this->assertMatchesRegularExpression('#^/?img/members/\d+\.jpg$#', $member['photo_url']);
        } else {
            $this->assertNull($member['photo_url']);
        }
    }
}
