<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class NavigationTest extends TestCase
{
    private static Client $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = loginClient();
    }

    public function testExpectedLinksFoundOnHomePage(): void
    {
        $resp = self::$client->get('/home');
        $html = (string)$resp->getBody();

        $expected = [
            '/AllMembers',
            '/EditMyDetails',
            '/MyFlights',
            '/PasswordChange',
            '/SignOut.php',
            '/Bookings',
            '/MessagingPage',
            '/MessagesTree',
            '/AllFlightsReportNew',
        ];

        foreach ($expected as $path) {
            $found = str_contains($html, "href=\"$path\"") || str_contains($html, "href='$path'");
            $this->assertTrue(
                $found,
                "Expected link $path not found on home page"
            );
        }
    }

    public function testHomeLoads(): void
    {
        $resp = self::$client->get('/home');
        $this->assertEquals(200, $resp->getStatusCode());
        $body = (string)$resp->getBody();
        $this->assertStringNotContainsString('Please logon', $body);
    }

    public function testAllMembersLoads(): void
    {
        $resp = self::$client->get('/AllMembers');
        $this->assertEquals(200, $resp->getStatusCode());
        $body = (string)$resp->getBody();
        $this->assertStringNotContainsString('Please logon', $body);
    }

    public function testEditMyDetailsLoads(): void
    {
        $resp = self::$client->get('/EditMyDetails', ['allow_redirects' => false]);
        // Should redirect to /MemberNew?id=5708
        $status = $resp->getStatusCode();
        $this->assertTrue(
            $status === 302 || $status === 200,
            "EditMyDetails returned $status"
        );
    }

    public function testMyFlightsLoads(): void
    {
        $resp = self::$client->get('/MyFlights');
        $this->assertEquals(200, $resp->getStatusCode());
    }

    public function testPasswordChangeLoads(): void
    {
        $resp = self::$client->get('/PasswordChange');
        $this->assertEquals(200, $resp->getStatusCode());
    }

    public function testBookingsLoads(): void
    {
        $resp = self::$client->get('/Bookings');
        $this->assertEquals(200, $resp->getStatusCode());
    }

    public function testMessagingPageLoads(): void
    {
        $resp = self::$client->get('/MessagingPage');
        $this->assertEquals(200, $resp->getStatusCode());
    }

    public function testMessagesTreeLoads(): void
    {
        $resp = self::$client->get('/MessagesTree');
        $this->assertEquals(200, $resp->getStatusCode());
    }

    public function testAllFlightsReportLoads(): void
    {
        $resp = self::$client->get('/AllFlightsReportNew');
        $this->assertEquals(200, $resp->getStatusCode());
    }

    public function testDailyOpsPagesLoad(): void
    {
        $pages = [
            '/StartDay.php?org=1',
            '/EditDailySheet?org=1',
            '/DailyLogSheet.php?org=1',
        ];

        foreach ($pages as $page) {
            $resp = self::$client->get($page);
            $this->assertEquals(200, $resp->getStatusCode(), "Failed: $page");
            $this->assertStringNotContainsString('Please logon', (string)$resp->getBody());
        }
    }

    public function testSignOutRedirects(): void
    {
        $resp = self::$client->get('/SignOut.php', ['allow_redirects' => false]);
        // Sign out destroys session and redirects to Login.php
        $this->assertTrue(
            $resp->getStatusCode() === 302 || $resp->getStatusCode() === 200
        );
    }

    public function testAllInternalNavLinks200(): void
    {
        // Re-login in case a previous test destroyed the session
        $localClient = loginClient();
        $resp = $localClient->get('/home');
        $html = (string)$resp->getBody();

        // Use DOMDocument for robust link extraction
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $links = [];
        foreach ($doc->getElementsByTagName('a') as $a) {
            $href = $a->getAttribute('href');
            if ($href !== '') {
                $links[] = $href;
            }
        }
        $links = array_unique($links);

        $skipped = 0;
        $tested = 0;
        foreach ($links as $href) {
            // Skip external, anchors, mailto, javascript
            if (preg_match('~^(http|//|mailto:|javascript:|#)~i', $href)) {
                $skipped++;
                continue;
            }

            // Resolve relative paths
            if (str_starts_with($href, '/')) {
                $url = $href;
            } else {
                continue; // skip weird relative paths
            }

            // Skip known external redirects and dead links
            if (str_contains($url, 'google.com') || str_contains($url, 'glidegreytown')) {
                $skipped++;
                continue;
            }

            $pageResp = $localClient->get($url, ['allow_redirects' => ['max' => 3, 'strict' => false]]);
            $status = $pageResp->getStatusCode();
            $pageBody = (string)$pageResp->getBody();
            $redirectedToLogin = str_contains($pageBody, 'Please logon');

            $this->assertTrue(
                $status === 200,
                "Link $url returned status $status"
            );
            $this->assertFalse(
                $redirectedToLogin,
                "Link $url redirected to login page"
            );
            $tested++;
        }

        $this->assertGreaterThanOrEqual(10, $tested, "Should test at least 10 internal links, tested $tested (skipped $skipped)");
    }
}
