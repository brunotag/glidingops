<?php

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use PHPUnit\Framework\TestCase;

/** One login per persona, test all pages (noisy routes excluded). */
class AccessMatrixTest extends TestCase
{
    private static \mysqli $con;
    private static int $fgUserId;

    private const PAGES = [
        ['/home',                    'auth'],
        ['/MyFlights',               'auth'],
        ['/MyFlightsCSV',            'auth'],
        ['/PasswordChange',          'auth'],
        ['/AllMembers',              'auth'],
        ['/EditMyDetails',           'auth'],
        ['/MemberNew',               'auth'],
        ['/AllFlightsReportNew',     'auth'],
        ['/AllFlightsMobile',        'auth'],
        ['/DailyLogSheet',           'auth'],
        ['/Bookings',                'auth'],
        ['/SeasonTrends',            'auth'],

        ['/DailySheet',              'daily-ops'],
        ['/StartDay',                'daily-ops'],
        ['/EditDailySheet',          'daily-ops'],
        ['/MessagingPage',           'daily-ops'],
        ['/MessagesTree',            'daily-ops'],
        ['/SentMessages',            'daily-ops'],

        ['/BillingReport',           'cfo'],
        ['/TowCharges',              'cfo,admin'],
        ['/TowCharge',               'cfo,admin'],
        ['/OtherCharges',            'cfo,admin'],
        ['/OtherCharge',             'cfo,admin'],
        ['/IncentiveSchemes',        'cfo,admin'],
        ['/IncentiveScheme',         'cfo,admin'],
        ['/SubsToSchemes',           'cfo,admin'],
        ['/SubsToScheme',            'cfo,admin'],
        ['/TreasurerReportNew3',     'cfo'],
        ['/TreasurerReportNew4',     'cfo'],

        ['/Users',                   'admin'],
        ['/UsersList',               'admin'],
        ['/Roles',                   'admin'],
        ['/Role',                    'admin'],
        ['/AircraftTypes',           'admin'],
        ['/AircraftType',            'admin'],
        ['/FlightTypes',             'admin'],
        ['/FlightType',              'admin'],
        ['/LaunchTypes',             'admin'],
        ['/LaunchType',              'admin'],
        ['/BillingOptions',          'admin'],
        ['/BillingOption',           'admin'],

        ['/Engineer',                'engineer'],

        ['/Organisations',           'god'],
        ['/Organisation',            'god'],
        ['/ViewAs',                  'god'],
        ['/InviteUsers',             'god'],

        ['/AllAircraft',             'admin,engineer,cfi,cfo'],
        ['/Aircraft',                'admin,engineer,cfi,cfo'],
    ];

    private const APIS = [
        ['GET', '/api/members',             'auth'],
        ['GET', '/api/member-form.php',     'auth'],
        ['GET', '/api/member-search?search=t', 'auth'],
        ['GET', '/api/members-email?search=t', 'auth'],
        ['GET', '/api/aircraft',            'auth'],
        ['GET', '/api/track-flights',       'auth'],
        ['GET', '/api/favourites',          'auth'],
        ['GET', '/api/texts',               'daily-ops'],
    ];

    private const PERSONA_TESTS = [
        'booking', 'daily-ops', 'cfo', 'cfi', 'engineer', 'admin', 'god'
    ];

    public static function setUpBeforeClass(): void
    {
        $db = require __DIR__ . '/../config/database.php';
        $p = $db['gliding'];
        self::$con = mysqli_connect($p['hostname'], $p['username'], $p['password'], $p['dbname']);
        if (!self::$con) throw new RuntimeException('DB fail');
        $r = mysqli_query(self::$con, "SELECT id FROM users WHERE usercode='fgordon'");
        $u = mysqli_fetch_assoc($r);
        self::$fgUserId = intval($u['id']);
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
              SELECT " . self::$fgUserId . ", p.id FROM personas p WHERE p.name='$persona'";
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

    private function shouldAllow(string $persona, string $rule): bool {
        if ($rule === 'auth') return true;
        return in_array($persona, explode(',', $rule));
    }

    private function testAll(string $persona): void
    {
        self::setP($persona);
        $client = self::login();

        foreach (self::PAGES as [$route, $rule]) {
            $resp = $client->get($route, ['allow_redirects' => false]);
            $status = $resp->getStatusCode();
            $bodyStr = (string)$resp->getBody();
            $expectAllow = $this->shouldAllow($persona, $rule);

            if ($expectAllow) {
                $ok = $status === 200 || ($status >= 300 && $status < 400);
                $this->assertTrue($ok, "[$persona] should ALLOW $route — got $status");
            } else {
                $denied = $status === 302 || $status === 403
                    || stripos($bodyStr, 'Not authorized') !== false;
                $this->assertTrue($denied, "[$persona] should DENY $route — got $status body=" . substr($bodyStr, 0, 60));
            }
        }

        foreach (self::APIS as [$method, $route, $rule]) {
            $resp = $client->request($method, $route, ['allow_redirects' => false]);
            $status = $resp->getStatusCode();
            $expectAllow = $this->shouldAllow($persona, $rule);

            if ($expectAllow) {
                $this->assertTrue($status === 200, "[$persona] should ALLOW $method $route — got $status");
            } else {
                $denied = $status === 302 || $status === 403;
                $this->assertTrue($denied, "[$persona] should DENY $method $route — got $status");
            }
        }
    }

    public function testBooking():   void { $this->testAll('booking'); }
    public function testDailyOps():  void { $this->testAll('daily-ops'); }
    public function testCfo():       void { $this->testAll('cfo'); }
    public function testCfi():       void { $this->testAll('cfi'); }
    public function testEngineer():  void { $this->testAll('engineer'); }
    public function testAdmin():     void { $this->testAll('admin'); }
    public function testGod():       void { $this->testAll('god'); }

    /** God can view home as another persona. */
    public function testViewAsOverride(): void
    {
        self::setP('god');
        $client = self::login();

        $resp = $client->get('/home/?as=booking', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertStringContainsString('Viewing as security level', (string)$resp->getBody());

        $resp2 = $client->get('/home/', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp2->getStatusCode());
        $this->assertStringNotContainsString('Viewing as security level', (string)$resp2->getBody());
    }

    /** Non-god user cannot use ?as= override. */
    public function testViewAsOverrideDenied(): void
    {
        self::setP('booking');
        $client = self::login();

        $resp = $client->get('/home/?as=god', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertStringNotContainsString('Viewing as security level', (string)$resp->getBody());
    }

    /** ?as= does not corrupt god session. */
    public function testViewAsDoesNotCorruptSession(): void
    {
        self::setP('god');
        $client = self::login();

        $client->get('/home/?as=booking', ['allow_redirects' => false]);
        $resp = $client->get('/ViewAs', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertStringNotContainsString('Not authorized', (string)$resp->getBody());
    }

    /** Unknown persona name defaults to security level 1. */
    public function testViewAsUnknownPersonaDefaultsToMember(): void
    {
        self::setP('god');
        $client = self::login();

        $resp = $client->get('/home/?as=member', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp->getStatusCode());
        $body = (string)$resp->getBody();
        $this->assertStringContainsString('Viewing as security level', $body);
        $this->assertStringContainsString('member', $body);
    }
}
