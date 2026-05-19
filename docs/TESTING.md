# Testing

## Overview

The project uses PHPUnit + GuzzleHttp for end-to-end integration tests. Tests run against the Vagrant dev box at `http://glidingops.test` — they exercise real HTTP requests through Apache, hitting actual PHP and MySQL. No mocking, no fixtures — every test is a full stack exercise.

## Setup

Dependencies are installed via the existing `lrv/composer.json`:

```bash
cd lrv; vagrant ssh -c "cd /home/vagrant/code/lrv && composer install"
```

This installs `phpunit/phpunit ^10.0` and `guzzlehttp/guzzle ^7.0` into `vendor/` in the project root.

## Running Tests

### All Tests

```bash
cd lrv; vagrant ssh -c "cd /home/vagrant/code && ./lrv/vendor/bin/phpunit"
```

### Specific Test Class

```bash
vagrant ssh -c "cd /home/vagrant/code && ./lrv/vendor/bin/phpunit --filter=HomePageTest"
```

### Single Test Method

```bash
vagrant ssh -c "cd /home/vagrant/code && ./vendor/bin/phpunit --filter=testCssGridPresent"
```

## Test Suite

### `HomePageTest` — Homepage Layout

| Test | What It Asserts |
|------|----------------|
| `testPageLoads` | HTTP 200 |
| `testCssGridPresent` | `grid-template-columns`, `grid-auto-flow: dense` in CSS |
| `testWideWidgetHeaders` | "Latest Updates", "My Gliding", "Flying / Tracking" card headers present |
| `testLatestUpdatesHeight` | `height:340px` on Latest Updates widget |
| `testLatestUpdatesLimit` | At most 4 `.msg-item` elements rendered (LIMIT 4) |
| `testDataMaintenanceAfterSuperAdmin` | Data Maintenance card appears after Super Admin in DOM |
| `testMemberNameGenitive` | Shows `'s last 5 flights` with member name |
| `testViewAllYourFlights` | Link text matches new phrasing |
| `testEditYourDetails` | Link text matches new phrasing |

### `PhotoUploadTest` — Photo Upload End-to-End

| Test | What It Asserts |
|------|----------------|
| `testUploadPhoto` | Creates 600x600 red JPEG, POSTs to `/api/member-form.php` with `photo` field. Asserts `success: true` and `photo_url` matches `/img/members/{id}.jpg` |
| `testPhotoIsResized` | GETs the uploaded photo URL, asserts file size < 50KB (proves GD resized from 600x600) |
| `testRejectNonImage` | Uploads a `.txt` file, asserts `photo_url: null` (rejected by MIME check) |

### `MemberListPhotoTest` — Member List Photo URLs

| Test | What It Asserts |
|------|----------------|
| `testPhotoUrlIsIdBased` | All `photo_url` values from `/api/members` match regex `/img/members/\d+\.jpg$` |
| `testNoDisplaynameBasedUrls` | No non-numeric filenames in photo URLs |
| `testApiReturnsJson` | Content-Type header contains `application/json` |
| `testKnownMemberPhotoFallback` | A specific member's photo URL follows the expected format (skips if member not found) |

### `HeaderPhotoTest` — Header Photo on All Pages

| Test | What It Asserts |
|------|----------------|
| `testHeaderOnHome` | `/home` contains `/img/members/` inside `.head-user` |
| `testHeaderOnAllMembers` | Same for `/AllMembers` |
| `testHeaderOnMyFlights` | Same for `/MyFlights` |
| `testHeaderNoprofileFallback` | `onerror` references `/img/noprofile.png` |
| `testNoprofileImageLoads` | `/img/noprofile.png` returns 200 and is > 1KB |
| `testHeaderPhotoSrcSetsMemberId` | Photo src path contains numeric member ID |

### `NavigationTest` — Page Navigation

| Test | What It Asserts |
|------|----------------|
| `testExpectedLinksFoundOnHomePage` | All 9 expected links present in home page HTML |
| `testHomeLoads` | Home page returns 200, not login redirect |
| `testAllMembersLoads` | `/AllMembers` returns 200 |
| `testEditMyDetailsLoads` | `/EditMyDetails` returns 200 or 302 |
| `testMyFlightsLoads` | `/MyFlights` returns 200 |
| `testPasswordChangeLoads` | `/PasswordChange` returns 200 |
| `testBookingsLoads` | `/Bookings` returns 200 |
| `testMessagingPageLoads` | `/MessagingPage` returns 200 |
| `testMessagesTreeLoads` | `/MessagesTree` returns 200 |
| `testAllFlightsReportLoads` | `/AllFlightsReportNew` returns 200 |
| `testDailyOpsPagesLoad` | `/StartDay.php?org=1`, `/EditDailySheet?org=1`, `/DailyLogSheet.php?org=1` all return 200 |
| `testSignOutRedirects` | `/SignOut.php` returns 302 (redirect to login) |
| `testAllInternalNavLinks200` | Extracts all `<a href>` from home page, tests each internal URL for 200 status and no login redirect. Currently tests ~47 links. |

## Architecture

### `tests/bootstrap.php`

Defines two helper functions:

```php
function loginClient(): Client
```

Returns a Guzzle client with an authenticated session cookie. POSTs to `/checklogin.php`, then GETs `/home` to establish the session. Uses `CookieJar` for automatic cookie persistence.

```php
function assertStatusCode($response, int $expected = 200, string $message = ''): void
```

Asserts HTTP status code with a helpful error message.

### `phpunit.xml`

Configures PHPUnit:
- Bootstrap: `tests/bootstrap.php`
- Test suite: `tests/` directory
- Colors enabled, cache disabled

### `composer.json`

```json
{
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "guzzlehttp/guzzle": "^7.0"
    }
}
```

Both `vendor/` and `composer.lock` are gitignored.

## Writing New Tests

1. Create a new file in `tests/` (e.g. `tests/MyFeatureTest.php`)
2. Extend `PHPUnit\Framework\TestCase`
3. Call `loginClient()` in `setUpBeforeClass` to get an authenticated client
4. Assert against HTTP responses and HTML content

Example skeleton:

```php
<?php
use PHPUnit\Framework\TestCase;

class MyFeatureTest extends TestCase
{
    private static Client $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = loginClient();
    }

    public function testPageLoads(): void
    {
        $resp = self::$client->get('/my-page');
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertStringNotContainsString('Please logon', (string)$resp->getBody());
    }
}
```

## Known Skipped / Pre-existing Issues

- `MemberListPhotoTest::testKnownMemberPhotoFallback` — Skipped if member ID 1 doesn't exist in the database

## Common Test Failures

| Symptom | Likely Cause |
|---------|-------------|
| Navigation tests find 0 links | Sign-out test ran first and destroyed the session (use a local `loginClient()` in the test method) |
| Photo upload returns `photo_url: null` | GD not installed on the test environment, or file type not in allowed list |
| Tests return login page HTML | Session cookie not set or expired — ensure `loginClient()` is called |
