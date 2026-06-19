<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

/** Tests for personas column in users list and assignable-persona filtering. */
class PersonaAssignmentTest extends TestCase
{
    public static function tearDownAfterClass(): void
    {
        restoreAllPersonas();
    }

    /** Users list API returns persona names field. */
    public function testUsersListReturnsPersonas(): void
    {
        $client = loginAsPersona('admin');

        $resp = $client->get('/api/users?draw=1&length=5', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp->getStatusCode());
        $body = json_decode((string)$resp->getBody(), true);
        $this->assertIsArray($body['data']);
        $this->assertArrayHasKey('personas', $body['data'][0]);
    }

    /** fgordon user (all personas restored) shows comma-separated list. */
    public function testFgordonShowsAllPersonas(): void
    {
        setPersona('admin');
        $con = testDb();
        $userId = fgordonUserId();
        mysqli_query($con, "INSERT IGNORE INTO user_personas (user_id, persona_id)
            SELECT $userId, p.id FROM personas p");

        $client = loginClient();
        $resp = $client->get('/api/users?draw=1&length=500', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp->getStatusCode());
        $body = json_decode((string)$resp->getBody(), true);
        $fgordon = null;
        foreach ($body['data'] as $u) {
            if ($u['id'] == $userId) { $fgordon = $u; break; }
        }
        $this->assertNotNull($fgordon, 'fgordon not in users list');
        $this->assertStringContainsString('admin', $fgordon['personas']);
        $this->assertStringContainsString('god', $fgordon['personas']);
    }

    /** Admin editor can only assign personas whose perms are a subset of admin's. */
    public function testAdminCanAssignSubsetPersonas(): void
    {
        $client = loginAsPersona('admin');
        $resp = $client->get('/api/user-form.php', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp->getStatusCode());
        $body = json_decode((string)$resp->getBody(), true);
        $names = array_column($body['personas'], 'name');
        $this->assertContains('admin', $names);
        $this->assertContains('member', $names);
        $this->assertNotContains('god', $names);
        $this->assertNotContains('cfo', $names);
        $this->assertNotContains('daily-ops', $names);
    }

    /** God editor can assign personas whose perms are within god's set. */
    public function testGodCanAssignSubsetPersonas(): void
    {
        $client = loginAsPersona('god');
        $resp = $client->get('/api/user-form.php', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp->getStatusCode());
        $body = json_decode((string)$resp->getBody(), true);
        $names = array_column($body['personas'], 'name');
        $this->assertContains('god', $names);
        $this->assertContains('booking', $names);
        $this->assertContains('member', $names);
        $this->assertNotContains('admin', $names);
        $this->assertNotContains('daily-ops', $names);
    }

    /** POST rejects personas whose permissions exceed the editor's. */
    public function testCannotAssignExceedingPersonasViaPost(): void
    {
        $con = testDb();
        $fgMemberId = fgordonMemberId();

        setPersona('admin');
        $client = loginClient();

        $dailyOpsId = personaId('daily-ops');
        $memberId = personaId('member');

        $usercode = 'test-assign-' . time();
        $resp = $client->post('/api/user-form.php', [
            'allow_redirects' => false,
            'form_params' => [
                'id' => '',
                'name' => 'TestUser',
                'usercode' => $usercode,
                'password' => 'test123',
                'expire' => '2030-12-31',
                'member' => $fgMemberId,
                'personas' => [$dailyOpsId, $memberId],
            ],
        ]);
        $this->assertEquals(200, $resp->getStatusCode());
        $body = json_decode((string)$resp->getBody(), true);
        $this->assertTrue($body['success'], 'user create should succeed');

        $newUserId = $body['user_id'];
        $r = mysqli_query($con, "SELECT persona_id FROM user_personas WHERE user_id=" . intval($newUserId));
        $assignedIds = [];
        while ($rw = mysqli_fetch_assoc($r)) $assignedIds[] = intval($rw['persona_id']);

        $this->assertContains($memberId, $assignedIds, 'member should be assigned');
        $this->assertNotContains($dailyOpsId, $assignedIds, 'daily-ops should NOT be assigned');

        mysqli_query($con, "DELETE FROM users WHERE id=" . intval($newUserId));
        mysqli_query($con, "DELETE FROM user_personas WHERE user_id=" . intval($newUserId));
    }
}
