<?php

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use PHPUnit\Framework\TestCase;

/** Tests for personas column in users list and assignable-persona filtering. */
class PersonaAssignmentTest extends TestCase
{
    private static \mysqli $con;
    private static int $fgUserId;
    private static int $fgMemberId;

    public static function setUpBeforeClass(): void
    {
        $db = require __DIR__ . '/../config/database.php';
        $p = $db['gliding'];
        self::$con = mysqli_connect($p['hostname'], $p['username'], $p['password'], $p['dbname']);
        if (!self::$con) throw new RuntimeException('DB fail');
        $r = mysqli_query(self::$con, "SELECT id, member FROM users WHERE usercode='fgordon'");
        $u = mysqli_fetch_assoc($r);
        self::$fgUserId = intval($u['id']);
        self::$fgMemberId = intval($u['member']);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$con) {
            mysqli_query(self::$con, "DELETE FROM user_personas WHERE user_id=" . self::$fgUserId);
            $q = "INSERT INTO user_personas (user_id, persona_id)
                  SELECT " . self::$fgUserId . ", p.id FROM personas p";
            mysqli_query(self::$con, $q);
            mysqli_close(self::$con);
        }
    }

    private static function setP(string $persona): void
    {
        mysqli_query(self::$con, "DELETE FROM user_personas WHERE user_id=" . self::$fgUserId);
        $q = "INSERT INTO user_personas (user_id, persona_id)
              SELECT " . self::$fgUserId . ", p.id FROM personas p WHERE p.name IN ('$persona', 'member')";
        $r = mysqli_query(self::$con, $q);
        if (!$r) throw new RuntimeException("setP($persona) failed");
    }

    private static function login(): Client
    {
        $jar = new CookieJar();
        $c = new Client([
            'base_uri' => 'http://glidingops.test',
            'cookies' => $jar,
            'allow_redirects' => ['max' => 5, 'strict' => false],
            'http_errors' => false,
            'headers' => ['Accept' => 'text/html,application/json,*/*'],
            'timeout' => 15,
        ]);
        $c->post('/checklogin.php', ['form_params' => ['user'=>'fgordon','pcode'=>'fgordon']]);
        return $c;
    }

    private static function getPIds(array $names): array
    {
        $ids = [];
        foreach ($names as $n) {
            $esc = mysqli_real_escape_string(self::$con, $n);
            $r = mysqli_query(self::$con, "SELECT id FROM personas WHERE name='$esc'");
            if ($rw = mysqli_fetch_assoc($r)) $ids[] = intval($rw['id']);
        }
        return $ids;
    }

    /** Users list API returns persona names field. */
    public function testUsersListReturnsPersonas(): void
    {
        self::setP('admin');
        $client = self::login();

        $resp = $client->get('/api/users?draw=1&length=5', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp->getStatusCode());
        $body = json_decode((string)$resp->getBody(), true);
        $this->assertIsArray($body['data']);
        $this->assertArrayHasKey('personas', $body['data'][0]);
    }

    /** fgordon user (all personas restored) shows comma-separated list. */
    public function testFgordonShowsAllPersonas(): void
    {
        self::setP('admin');
        mysqli_query(self::$con, "INSERT IGNORE INTO user_personas (user_id, persona_id)
            SELECT " . self::$fgUserId . ", p.id FROM personas p");

        $client = self::login();
        $resp = $client->get('/api/users?draw=1&length=500', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp->getStatusCode());
        $body = json_decode((string)$resp->getBody(), true);
        $fgordon = null;
        foreach ($body['data'] as $u) {
            if ($u['id'] == self::$fgUserId) { $fgordon = $u; break; }
        }
        $this->assertNotNull($fgordon, 'fgordon not in users list');
        $this->assertStringContainsString('admin', $fgordon['personas']);
        $this->assertStringContainsString('god', $fgordon['personas']);
    }

    /** Admin editor can only assign personas whose perms are a subset of admin's. */
    public function testAdminCanAssignSubsetPersonas(): void
    {
        self::setP('admin');
        $client = self::login();
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
        self::setP('god');
        $client = self::login();
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
        self::setP('admin');
        $client = self::login();

        $dailyOpsId = self::getPIds(['daily-ops'])[0];
        $memberId = self::getPIds(['member'])[0];

        $resp = $client->post('/api/user-form.php', [
            'allow_redirects' => false,
            'form_params' => [
                'id' => '',
                'name' => 'TestUser',
                'usercode' => 'test-assign-' . time(),
                'password' => 'test123',
                'expire' => '2030-12-31',
                'member' => self::$fgMemberId,
                'personas' => [$dailyOpsId, $memberId],
            ],
        ]);
        $this->assertEquals(200, $resp->getStatusCode());
        $body = json_decode((string)$resp->getBody(), true);
        $this->assertTrue($body['success'], 'user create should succeed');

        $newUserId = $body['user_id'];
        $r = mysqli_query(self::$con, "SELECT persona_id FROM user_personas WHERE user_id=" . intval($newUserId));
        $assignedIds = [];
        while ($rw = mysqli_fetch_assoc($r)) $assignedIds[] = intval($rw['persona_id']);

        $this->assertContains($memberId, $assignedIds, 'member should be assigned');
        $this->assertNotContains($dailyOpsId, $assignedIds, 'daily-ops should NOT be assigned');

        mysqli_query(self::$con, "DELETE FROM users WHERE id=" . intval($newUserId));
        mysqli_query(self::$con, "DELETE FROM user_personas WHERE user_id=" . intval($newUserId));
    }
}
