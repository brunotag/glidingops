<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class UserCrudTest extends TestCase
{
    private static Client $client;
    private ?int $createdId = null;
    private ?string $createdUsercode = null;

    public static function setUpBeforeClass(): void
    {
        self::$client = loginAsPersona('admin');
    }

    public static function tearDownAfterClass(): void
    {
        restoreAllPersonas();
    }

    protected function tearDown(): void
    {
        if ($this->createdId) {
            $con = testDb();
            mysqli_query($con, "DELETE FROM user_personas WHERE user_id=" . intval($this->createdId));
            mysqli_query($con, "DELETE FROM users WHERE id=" . intval($this->createdId));
        }
    }

    public function testCreateUser(): void
    {
        $uid = uniqueId();
        $usercode = 'crud-' . $uid;

        $resp = self::$client->post('/api/user-form.php', [
            'form_params' => [
                'id' => '',
                'name' => 'Test User ' . $uid,
                'usercode' => $usercode,
                'password' => 'testpass123',
                'expire' => '2030-12-31',
                'member' => fgordonMemberId(),
            ],
        ]);

        $this->assertEquals(200, $resp->getStatusCode());
        $body = json_decode((string)$resp->getBody(), true);
        $this->assertTrue($body['success'], 'create failed: ' . ($body['message'] ?? ''));
        $this->assertNotEmpty($body['user_id']);

        $this->createdId = intval($body['user_id']);
        $this->createdUsercode = $usercode;

        assertRowExists(testDb(), 'users', [
            'id' => $this->createdId,
            'usercode' => $usercode,
        ], 'Created user should exist in DB');
    }

    public function testEditUser(): void
    {
        // First create
        $uid = uniqueId();
        $usercode = 'edit-' . $uid;

        $resp = self::$client->post('/api/user-form.php', [
            'form_params' => [
                'id' => '',
                'name' => 'Edit User ' . $uid,
                'usercode' => $usercode,
                'password' => 'testpass123',
                'expire' => '2030-12-31',
                'member' => fgordonMemberId(),
            ],
        ]);
        $body = json_decode((string)$resp->getBody(), true);
        $this->createdId = intval($body['user_id']);
        $this->createdUsercode = $usercode;

        // Now edit — change the name
        $newName = 'Edited Name ' . $uid;
        $resp2 = self::$client->post('/api/user-form.php', [
            'form_params' => [
                'id' => $this->createdId,
                'name' => $newName,
                'usercode' => $usercode,
                'expire' => '2031-06-30',
                'member' => fgordonMemberId(),
            ],
        ]);

        $this->assertEquals(200, $resp2->getStatusCode());
        $body2 = json_decode((string)$resp2->getBody(), true);
        $this->assertTrue($body2['success'], 'edit failed: ' . ($body2['message'] ?? ''));

        assertRowExists(testDb(), 'users', [
            'id' => $this->createdId,
            'name' => $newName,
        ], 'Edited user name should be updated in DB');
    }

    public function testCreateUserAssignsPersonas(): void
    {
        $uid = uniqueId();
        $usercode = 'perm-' . $uid;
        $memberPid = personaId('member');

        $resp = self::$client->post('/api/user-form.php', [
            'form_params' => [
                'id' => '',
                'name' => 'Perm User ' . $uid,
                'usercode' => $usercode,
                'password' => 'testpass123',
                'expire' => '2030-12-31',
                'member' => fgordonMemberId(),
                'personas' => [$memberPid],
            ],
        ]);

        $body = json_decode((string)$resp->getBody(), true);
        $this->assertTrue($body['success']);
        $this->createdId = intval($body['user_id']);
        $this->createdUsercode = $usercode;

        // Verify persona was assigned
        $con = testDb();
        $r = mysqli_query($con, "SELECT persona_id FROM user_personas WHERE user_id=" . $this->createdId);
        $assignedIds = [];
        while ($rw = mysqli_fetch_assoc($r)) $assignedIds[] = intval($rw['persona_id']);
        $this->assertContains($memberPid, $assignedIds, 'member persona should be assigned');
    }

    public function testPasswordChangeLoads(): void
    {
        $client = loginAsPersona('member');
        $resp = $client->get('/PasswordChange');
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertStringContainsString('Change', (string)$resp->getBody());
    }

    public function testCreateUserRejectsMissingRequiredFields(): void
    {
        $resp = self::$client->post('/api/user-form.php', [
            'form_params' => [
                'id' => '',
                'name' => '',
                'usercode' => '',
                'expire' => '',
            ],
        ]);

        $body = json_decode((string)$resp->getBody(), true);
        $this->assertFalse($body['success'], 'Should reject empty required fields');
        $this->assertStringContainsString('required', strtolower($body['message'] ?? ''));
    }
}
