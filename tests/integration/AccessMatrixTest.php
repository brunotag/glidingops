<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

/** One login per persona, test all pages (noisy routes excluded). */
class AccessMatrixTest extends TestCase
{
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
        ['/TowCharges',              'cfo'],
        ['/TowCharge',               'cfo'],
        ['/OtherCharges',            'cfo'],
        ['/OtherCharge',             'cfo'],
        ['/IncentiveSchemes',        'cfo'],
        ['/IncentiveScheme',         'cfo'],
        ['/SubsToSchemes',           'cfo'],
        ['/SubsToScheme',            'cfo'],
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

    public static function tearDownAfterClass(): void
    {
        restoreAllPersonas();
    }

    private function shouldAllow(string $persona, string $rule): bool {
        if ($rule === 'auth') return true;
        return in_array($persona, explode(',', $rule));
    }

    private function testAll(string $persona): void
    {
        $client = loginAsPersona($persona);

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
        $client = loginAsPersona('god');

        $resp = $client->get('/home/?as=booking', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertStringContainsString('Viewing as persona', (string)$resp->getBody());
        $this->assertStringContainsString('booking', (string)$resp->getBody());

        $resp2 = $client->get('/home/', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp2->getStatusCode());
        $this->assertStringNotContainsString('Viewing as persona', (string)$resp2->getBody());
    }

    /** Non-god user cannot use ?as= override. */
    public function testViewAsOverrideDenied(): void
    {
        $client = loginAsPersona('booking');

        $resp = $client->get('/home/?as=god', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertStringNotContainsString('Viewing as security level', (string)$resp->getBody());
    }

    /** ?as= does not corrupt god session. */
    public function testViewAsDoesNotCorruptSession(): void
    {
        setPersona('god');
        $client = loginClient();

        $client->get('/home/?as=booking', ['allow_redirects' => false]);
        $resp = $client->get('/ViewAs', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertStringNotContainsString('Not authorized', (string)$resp->getBody());
    }

    /** ViewAs with edit_favs loads the target member's favourites, not the viewer's own. */
    public function testViewAsFavouritesOverride(): void
    {
        $con = testDb();
        $fgMemberId = fgordonMemberId();
        $targetMemberId = 2; // Rod Ruddick

        // Ensure a known favourite exists for the target member
        $testHref = '/test-viewas-fav-' . bin2hex(random_bytes(4));
        $testLabel = 'Test ViewAs Fav';
        mysqli_query($con, "DELETE FROM user_favourites WHERE member_id = $targetMemberId AND href = '$testHref'");
        mysqli_query($con, "INSERT INTO user_favourites (member_id, href, label) VALUES ($targetMemberId, '$testHref', '$testLabel')");

        // Also ensure fgordon does NOT have this favourite (to prove we're not seeing fgordon's)
        mysqli_query($con, "DELETE FROM user_favourites WHERE member_id = $fgMemberId AND href = '$testHref'");

        $client = loginAsPersona('god');

        $resp = $client->get("/home/?as=member&edit_favs=1&edit_member_id=$targetMemberId", ['allow_redirects' => false]);
        $this->assertEquals(200, $resp->getStatusCode());
        $body = (string)$resp->getBody();

        // Should show target member's favourite
        $this->assertStringContainsString($testHref, $body);
        $this->assertStringContainsString($testLabel, $body);

        // favMemberId should be the target, not fgordon
        $this->assertStringContainsString("var favMemberId = $targetMemberId", $body);
        $this->assertStringNotContainsString("var favMemberId = $fgMemberId", $body);

        // editFavs should be true
        $this->assertStringContainsString('var editFavs = true', $body);

        // Banner should mention editing the target member's favourites
        $this->assertStringContainsString("editing <strong>Rod Ruddick</strong>'s favourites", $body);

        // Clean up the test favourite
        mysqli_query($con, "DELETE FROM user_favourites WHERE member_id = $targetMemberId AND href = '$testHref'");
    }

    /** Unknown persona name defaults to member. */
    public function testViewAsUnknownPersonaDefaultsToMember(): void
    {
        $client = loginAsPersona('god');

        $resp = $client->get('/home/?as=member', ['allow_redirects' => false]);
        $this->assertEquals(200, $resp->getStatusCode());
        $body = (string)$resp->getBody();
        $this->assertStringContainsString('Viewing as persona', $body);
        $this->assertStringContainsString('member', $body);
    }
}
