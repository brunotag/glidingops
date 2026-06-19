# Testing

## Overview

The project uses PHPUnit 9 with two test suites:

| Suite | Directory | Config | Tests | Requires |
|-------|-----------|--------|-------|----------|
| **Unit** | `tests/unit/` | `phpunit.unit.xml` | 53 pure function tests (billing calc) | PHP on Vagrant |
| **Integration** | `tests/integration/` | `phpunit.xml` | 72 HTTP + DB tests | Vagrant box (Apache, MySQL) |

Unit tests exercise pure PHP functions with no HTTP, no DB, no session. They run in ~0.05s.

Integration tests use GuzzleHttp against the Vagrant dev box at `http://glidingops.test` — real HTTP requests through Apache, hitting actual PHP and MySQL. No mocking, no fixtures — every test is a full stack exercise.

## Setup

Dependencies are installed via `lrv/composer.json`:

```bash
cd lrv; vagrant ssh -c "cd /home/vagrant/code/lrv && composer install"
```

This installs `phpunit/phpunit ^9.0` and `guzzlehttp/guzzle ^7.0` into `lrv/vendor/`.

## Running Tests

Use the helper script from the project root:

### Integration Tests (default)

```powershell
.\tools\run-tests
```

### Unit Tests Only

```powershell
.\tools\run-tests -Unit
```

### With a Filter

```powershell
.\tools\run-tests -Filter NavigationTest
.\tools\run-tests -Unit -Filter BillingReportTest
```

The underlying commands (if not using `run-tests.ps1`):

```bash
cd lrv; vagrant ssh -c "cd /home/vagrant/code && ./lrv/vendor/bin/phpunit"                              # integration
cd lrv; vagrant ssh -c "cd /home/vagrant/code && ./lrv/vendor/bin/phpunit -c phpunit.unit.xml"          # unit
cd lrv; vagrant ssh -c "cd /home/vagrant/code && ./lrv/vendor/bin/phpunit --filter=HomePageTest"        # specific test
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

### `AccessMatrixTest` — Permission Access Matrix

| Test | What It Asserts |
|------|----------------|
| `testBooking` | Booking persona can access auth-level pages, denied from daily-ops+ pages |
| `testDailyOps` | Daily-ops can access daily-ops pages, denied from CFO+ pages |
| `testCfo` | CFO can access billing pages, denied from admin+ |
| `testCfi` | CFI can access only shared aircraft pages |
| `testEngineer` | Engineer can access engineer + shared aircraft pages |
| `testAdmin` | Admin can access user/type management pages |
| `testGod` | God can access organisations, ViewAs, permissions |
| `testViewAsOverride` | God ?as=booking overrides permissions per-request without corrupting session |
| `testViewAsOverrideDenied` | Booking cannot use ?as=god |
| `testViewAsDoesNotCorruptSession` | Session is not corrupted after ?as= use |
| `testViewAsUnknownPersonaDefaultsToMember` | Unknown persona name in ?as= falls back to member level |

Tests 7 personas against ~50 pages + 8 API endpoints (392 total assertions).

### `PersonaAssignmentTest` — Persona Assignment

| Test | What It Asserts |
|------|----------------|
| `testUsersListReturnsPersonas` | `/api/users` includes `personas` field |
| `testFgordonShowsAllPersonas` | fgordon user shows comma-separated persona names |
| `testAdminCanAssignSubsetPersonas` | Admin editor sees only admin-subset personas (not god, not cfo) |
| `testGodCanAssignSubsetPersonas` | God editor sees only god-subset personas (not admin) |
| `testCannotAssignExceedingPersonasViaPost` | POST with exceeding persona IDs is filtered server-side |

### `MemberCrudTest` — Member Create/Edit/Search

| Test | What It Asserts |
|------|----------------|
| `testCreateMember` | POST to `/api/member-form.php` creates member, verifies DB row |
| `testEditMember` | Create then edit member, verify surname updated in DB |
| `testSearchMemberReturnsResults` | Created member appears in `/api/member-search` results |
| `testCreateMemberRejectsMissingRequiredFields` | Empty names/class/status returns `success: false` |

### `UserCrudTest` — User Create/Edit

| Test | What It Asserts |
|------|----------------|
| `testCreateUser` | POST to `/api/user-form.php` creates user, verifies DB row |
| `testEditUser` | Create then edit user name, verify updated in DB |
| `testCreateUserAssignsPersonas` | Persona IDs passed in POST are written to `user_personas` |
| `testPasswordChangeLoads` | `/PasswordChange` returns 200 |
| `testCreateUserRejectsMissingRequiredFields` | Empty name/usercode returns `success: false` |

### `MessagingTest` — Broadcast Messages

| Test | What It Asserts |
|------|----------------|
| `testSendBroadcast` | POST to `/MessagingPage.php` with recipients returns JSON with `success > 0` |
| `testMessageAppearsInMessagesTree` | Sent message UID appears in `/MessagesTree` HTML |
| `testMemberCannotAccessMessaging` | Member persona gets 403/302 on `/MessagingPage` |
| `testBookingCannotAccessMessaging` | Booking persona gets 403/302 |
| `testCfiCannotAccessMessaging` | CFI persona gets 403/302 |

### `ErrorPageTest` — Error Handling

| Test | What It Asserts |
|------|----------------|
| `testUnauthenticatedRedirectsToLogin` | No-session request to `/home` gets 301/302/401 |
| `test404ReturnsBrandedPage` | `/nonexistent-page` returns branded 404 page via ErrorDocument |
| `testMemberGets403OnAdminPage` | Member accessing `/Users` gets 403 (JSON redirect or direct) |
| `test403BrandedPageRenders` | Direct request to `/error-page.php?code=403` shows "Access Denied" |
| `testAllErrorCodesRender` | All 5 error codes (400/403/404/500/503) render with correct HTTP status + body |

### `BillingReportTest` (Unit) — Pure Function Tests

53 tests covering `calcGliderCharge`, `calcLaunchCharge`, `isCompetitionFlight`, `isFiftyFifty`, `getLaunchLabel`, and combined known-flight calculations. Uses data providers with class-specific rates (Youth discount, Junior full-rate, caps, edge cases).

### `BillingReportTest` (Integration) — Page Load + CSV

2 tests: billing report page loads with "Treasurer Monthly Report" heading, CSV export downloads with `text/csv` content type.

## Architecture

### Directory Layout

```
tests/
  unit/
    bootstrap.php           # loads vendor autoload + helpers/billing-calc.php
    BillingReportTest.php   # pure function tests only
  integration/
    bootstrap.php           # loads vendor autoload + Guzzle + TestHelper
    TestHelper.php          # shared helpers for persona/DB/assertions
    AccessMatrixTest.php
    PersonaAssignmentTest.php
    MemberCrudTest.php
    UserCrudTest.php
    MessagingTest.php
    ErrorPageTest.php
    HomePageTest.php
    NavigationTest.php
    PhotoUploadTest.php
    MemberListPhotoTest.php
    HeaderPhotoTest.php
    BillingReportTest.php   # e2e page-load tests only
```

### Config Files

| File | Suite | Bootstrap | Tests Directory |
|------|-------|-----------|-----------------|
| `phpunit.xml` | Integration | `tests/integration/bootstrap.php` | `tests/integration/` |
| `phpunit.unit.xml` | Unit | `tests/unit/bootstrap.php` | `tests/unit/` |

### `tests/integration/bootstrap.php`

Defines:

```php
function loginClient(): Client
```

Returns a Guzzle client with an authenticated session cookie. POSTs to `/checklogin.php`, then GETs `/home` to establish the session. Uses `CookieJar` for automatic cookie persistence.

```php
function assertStatusCode($response, int $expected = 200, string $message = ''): void
```

Asserts HTTP status code with a helpful error message.

```php
function assertNotLoginRedirect($response, string $url): void
```

Asserts the response is not a login redirect (checks for "Please logon" and "Sign Out").

### `tests/integration/TestHelper.php`

Shared helpers for integration tests:

| Function | Purpose |
|----------|---------|
| `testDb()` | Cached mysqli connection to gliding database |
| `fgordonUserId()` | fgordon's `users.id` |
| `fgordonMemberId()` | fgordon's `members.id` (linked member) |
| `setPersona(string\|array)` | Wipe + assign specific persona(s) to fgordon (always includes `member`) |
| `restoreAllPersonas()` | Restore all personas to fgordon (call in `tearDownAfterClass`) |
| `loginAsPersona(string)` | `setPersona()` + `loginClient()` — one-shot login with specific persona |
| `personaId(string)` | Lookup persona ID by name |
| `uniqueId()` | Timestamp + random hex suffix for test data |
| `assertRowExists($con, $table, $conditions)` | Assert DB row exists matching conditions |
| `assertRowMissing($con, $table, $conditions)` | Assert no DB row matches conditions |

### `composer.json`

Both `vendor/` and `composer.lock` are gitignored. Dependencies in `lrv/composer.json`:

```json
{
    "require-dev": {
        "phpunit/phpunit": "^9.0",
        "guzzlehttp/guzzle": "^7.0"
    }
}
```

## Writing New Tests

### Unit Test

1. Create a new file in `tests/unit/` (e.g. `tests/unit/MyCalcTest.php`)
2. Extend `PHPUnit\Framework\TestCase`
3. No bootstrap setup needed — `helpers/billing-calc.php` is auto-loaded
4. Assert against pure function outputs

```php
<?php
use PHPUnit\Framework\TestCase;

class MyCalcTest extends TestCase
{
    /** @dataProvider chargeProvider */
    public function testCalc($input, $expected)
    {
        $result = calcGliderCharge(...$input);
        $this->assertEquals($expected, $result);
    }

    public function chargeProvider(): array { /* ... */ }
}
```

### Integration Test

1. Create a new file in `tests/integration/` (e.g. `tests/integration/MyFeatureTest.php`)
2. Extend `PHPUnit\Framework\TestCase`
3. Use `loginClient()` or `loginAsPersona('persona-name')` to get an authenticated Guzzle client
4. Use `testDb()` + `assertRowExists()` for DB assertions
5. Call `restoreAllPersonas()` in `tearDownAfterClass` if you changed personas
6. Clean up created DB records in `tearDown()`

```php
<?php
use GuzzleHttp\Client;
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

### Testing With Specific Personas

Integration tests that need to verify permission gates use `loginAsPersona()`:

```php
// Member sees their own data but not admin pages
$client = loginAsPersona('member');
$resp = $client->get('/Users', ['allow_redirects' => false]);
$this->assertTrue($resp->getStatusCode() === 302 || $resp->getStatusCode() === 403);

// Daily-ops can send messages
$client = loginAsPersona('daily-ops');
$resp = $client->post('/MessagingPage.php', ['form_params' => [...]]);
```

Available personas: `member`, `booking`, `daily-ops`, `cfo`, `cfi`, `engineer`, `admin`, `god`.

## Known Skipped / Pre-existing Issues

- `MemberListPhotoTest::testKnownMemberPhotoFallback` — Skipped if member ID 1 doesn't exist in the database
- `AccessMatrixTest::testAdmin` — `/TowCharges` etc. correctly return 403 for admin (these routes require CFO permission, not admin)

## Common Test Failures

| Symptom | Likely Cause |
|---------|-------------|
| Navigation tests find 0 links | Sign-out test ran first and destroyed the session (use a local `loginClient()` in the test method) |
| Photo upload returns `photo_url: null` | GD not installed on the test environment, or file type not in allowed list |
| Tests return login page HTML | Session cookie not set or expired — ensure `loginClient()` is called |
| Messaging test returns `null` instead of JSON array | BOM (`\xEF\xBB\xBF`) in MessagingPage.php response — `json_decode` fails. The `jsonDecode()` helper in MessagingTest strips it. |
| Integration tests fail with "require failed" | Path is off by one directory level — check bootstrap/TestHelper includes use `../../` from `tests/integration/` |
| `setPersona()` throws "Persona 'X' not found" | Persona name doesn't exist in the DB. Check `personas` table. |
