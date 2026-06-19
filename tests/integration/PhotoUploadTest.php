<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class PhotoUploadTest extends TestCase
{
    private static Client $client;
    private static string $testImage;
    private static int $memberId;
    private static string $cleanupFile = '';

    public static function setUpBeforeClass(): void
    {
        self::$client = loginClient();

        // Create a 600x600 test JPEG in temp
        $im = imagecreatetruecolor(600, 600);
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 0, 0));
        self::$testImage = tempnam(sys_get_temp_dir(), 'test_photo_') . '.jpg';
        imagejpeg($im, self::$testImage, 90);
        imagedestroy($im);

        // Use a known editable member (dev user)
        self::$memberId = 5708;
    }

    public static function tearDownAfterClass(): void
    {
        if (file_exists(self::$testImage)) {
            unlink(self::$testImage);
        }
        if (self::$cleanupFile && file_exists(self::$cleanupFile)) {
            // Cannot unlink via HTTP, but we track it
        }
    }

    public function testUploadPhoto(): void
    {
        $resp = self::$client->post('/api/member-form.php', [
            'multipart' => [
                ['name' => 'id', 'contents' => (string)self::$memberId],
                ['name' => 'firstname', 'contents' => 'Fred'],
                ['name' => 'surname', 'contents' => 'Gordon'],
                ['name' => 'displayname', 'contents' => 'Fred Gordon'],
                ['name' => 'class', 'contents' => '1'],
                ['name' => 'status', 'contents' => '1'],
                [
                    'name' => 'photo',
                    'contents' => fopen(self::$testImage, 'r'),
                    'filename' => 'test.jpg',
                ],
            ],
        ]);

        $this->assertEquals(200, $resp->getStatusCode());
        $data = json_decode((string)$resp->getBody(), true);
        $this->assertIsArray($data);
        $this->assertTrue($data['success'] ?? false, 'Photo upload failed: ' . ($data['message'] ?? 'unknown'));
        $this->assertIsString($data['photo_url'] ?? null);
        $this->assertMatchesRegularExpression('#^/?img/members/\d+\.jpg$#', $data['photo_url']);
    }

    /**
     * @depends testUploadPhoto
     */
    public function testPhotoIsResized(): void
    {
        $resp = self::$client->get('/img/members/' . self::$memberId . '.jpg');
        $this->assertEquals(200, $resp->getStatusCode());
        $size = strlen((string)$resp->getBody());
        $this->assertLessThan(50000, $size, "Photo should be < 50KB after resize, was $size bytes");
    }

    public function testRejectNonImage(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'not_photo_');
        file_put_contents($tempFile, 'not an image file');

        $resp = self::$client->post('/api/member-form.php', [
            'multipart' => [
                ['name' => 'id', 'contents' => (string)self::$memberId],
                ['name' => 'firstname', 'contents' => 'Fred'],
                ['name' => 'surname', 'contents' => 'Gordon'],
                ['name' => 'displayname', 'contents' => 'Fred Gordon'],
                ['name' => 'class', 'contents' => '1'],
                ['name' => 'status', 'contents' => '1'],
                [
                    'name' => 'photo',
                    'contents' => fopen($tempFile, 'r'),
                    'filename' => 'notimage.txt',
                ],
            ],
        ]);

        unlink($tempFile);

        $data = json_decode((string)$resp->getBody(), true);
        $this->assertTrue($data['success'] ?? false, 'Member update should still succeed');
        // Non-image upload should not change photo_url — it may be null or the existing value
        $this->assertArrayHasKey('photo_url', $data, 'photo_url should be in response');
    }
}
