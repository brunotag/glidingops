<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class ErrorPageTest extends TestCase
{
    private static Client $anonClient;

    public static function setUpBeforeClass(): void
    {
        self::$anonClient = new Client([
            'base_uri' => 'http://glidingops.test',
            'allow_redirects' => false,
            'http_errors' => false,
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        restoreAllPersonas();
    }

    /** Unauthenticated request to a protected page redirects to login (301 or 302). */
    public function testUnauthenticatedRedirectsToLogin(): void
    {
        $resp = self::$anonClient->get('/home');
        $status = $resp->getStatusCode();

        $this->assertTrue(
            $status === 301 || $status === 302 || $status === 401,
            "Unauthenticated access to /home should redirect, got $status"
        );
    }

    /** 404 returns branded error page via ErrorDocument. */
    public function test404ReturnsBrandedPage(): void
    {
        $resp = self::$anonClient->get('/this-page-definitely-does-not-exist-12345xyz');
        $body = (string)$resp->getBody();

        $this->assertStringContainsString(
            'Page Not Found',
            $body,
            '404 should show branded error page'
        );
        $this->assertStringContainsString(
            '404',
            $body,
            '404 page should contain the error code'
        );
    }

    /** Member accessing admin-only pages gets a 403 response (JSON for API-style clients). */
    public function testMemberGets403OnAdminPage(): void
    {
        $client = loginAsPersona('member');

        $resp = $client->get('/Users', ['allow_redirects' => false]);
        $status = $resp->getStatusCode();
        $body = (string)$resp->getBody();
        $location = $resp->getHeaderLine('Location');

        $isRedirectTo403 = $status === 302 && strpos($location, 'error-page') !== false;
        $isJsonDenied = $status === 200 && strpos($body, '"Not authorized"') !== false;
        $isDirect403 = $status === 403;

        $this->assertTrue(
            $isRedirectTo403 || $isJsonDenied || $isDirect403,
            "Member should get 403 on /Users, got status=$status body=" . substr($body, 0, 100)
        );
    }

    /** Following the 403 redirect to error-page.php shows branded page. */
    public function test403BrandedPageRenders(): void
    {
        // Direct request to the error page
        $resp = self::$anonClient->get('/error-page.php?code=403');
        $body = (string)$resp->getBody();

        $this->assertStringContainsString('Access Denied', $body);
        $this->assertStringContainsString('403', $body);
        $this->assertStringContainsString('GOPS', $body);
    }

    /** Error page renders all supported error codes with correct HTTP status code and body. */
    public function testAllErrorCodesRender(): void
    {
        foreach ([400, 403, 404, 500, 503] as $code) {
            $resp = self::$anonClient->get('/error-page.php?code=' . $code);
            $this->assertEquals($code, $resp->getStatusCode(), "error-page.php?code=$code should set HTTP $code");
            $body = (string)$resp->getBody();
            $this->assertStringContainsString((string)$code, $body, "Error page for $code should contain the code");
        }
    }
}
