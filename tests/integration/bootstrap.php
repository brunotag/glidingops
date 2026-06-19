<?php

require __DIR__ . '/../../lrv/vendor/autoload.php';
require __DIR__ . '/TestHelper.php';

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

function loginClient(): Client
{
    $jar = new CookieJar();
    $client = new Client([
        'base_uri' => 'http://glidingops.test',
        'cookies' => $jar,
        'allow_redirects' => ['max' => 5, 'strict' => false],
        'http_errors' => false,
        'headers' => ['Accept' => 'text/html,application/json,*/*'],
    ]);

    $resp = $client->post('/checklogin.php', [
        'form_params' => ['user' => 'fgordon', 'pcode' => 'fgordon'],
    ]);

    // Follow redirects to establish session
    $client->get('/home');

    return $client;
}

function assertStatusCode($response, int $expected = 200, string $message = ''): void
{
    $actual = $response->getStatusCode();
    $uri = $response->getHeader('X-Guzzle-Redirect-History')[0] ?? $response->getRequest()->getUri();
    if ($actual !== $expected) {
        $body = substr((string)$response->getBody(), 0, 200);
        throw new RuntimeException(
            ($message ? "$message: " : '') . "Expected $expected, got $actual for $uri. Body: $body"
        );
    }
}

function assertNotLoginRedirect($response, string $url): void
{
    $body = (string)$response->getBody();
    if (stripos($body, 'Please logon') !== false || stripos($body, 'Sign Out') === false) {
        throw new RuntimeException("Redirected to login for $url");
    }
}
