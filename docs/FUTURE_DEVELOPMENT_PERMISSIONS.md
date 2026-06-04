# Future Development: Persona-Based Permission System

## Motivation

Replace the current bitmask-based security level (`$_SESSION['security'] & N`) with a flexible, DB-driven permission system. This allows granular control over what each user can do, supports many-to-many persona assignment, and eliminates the rigid bitmask model.

## Current System

- **Bitmask** stored in `users.securitylevel` (1=Member, 4=Daily Ops, 8=CFO, etc.)
- Pages check with `$_SESSION['security'] & N` or `$_SESSION['security'] < N`
- 12 unique security level values currently assigned to ~356 users
- Combined levels (e.g., 5 = Member + Daily Ops) are just bitwise sums

## New System

### Key Decisions

- **No "Member" persona** — authentication is sufficient for basic access. Future dormant-member logic will handle access revocation.
- **Personas based on atomic bits** (not composite values): booking, daily-ops, cfo, cfi, engineer, admin, god, service-user
- **OR logic** for combined checks — a page requiring admin or engineer can be accessed if the user has either persona
- **MessagingPage** requires only `daily-ops` (removing the old member requirement)
- **Secret code / service user** hardcodes `daily-ops` persona permissions
- **No hardcoded mappings** — permissions are DB rows linked to personas via `persona_permissions`

---

## New Tables

```sql
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,       -- e.g. 'daily-sheet.access'
    description TEXT
);

CREATE TABLE personas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,       -- 'admin', 'daily-ops', etc.
    description TEXT
);

CREATE TABLE persona_permissions (
    persona_id INT NOT NULL REFERENCES personas(id),
    permission_id INT NOT NULL REFERENCES permissions(id),
    PRIMARY KEY (persona_id, permission_id)
);

CREATE TABLE user_personas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id),
    persona_id INT NOT NULL REFERENCES personas(id),
    org_id INT UNSIGNED,                    -- NULL = cross-org
    UNIQUE (user_id, persona_id, org_id)
);
```

## Seed Data

### 8 Personas

| name | description | bit |
|------|-------------|-----|
| `booking` | Booking management | 2 |
| `daily-ops` | Flight entry, daily operations | 4 |
| `cfo` | Billing and treasurer reports | 8 |
| `cfi` | Chief Flight Instructor | 16 |
| `engineer` | Aircraft maintenance | 32 |
| `admin` | System administration | 64 |
| `god` | Super admin, org management | 128 |
| `service-user` | Secret code — daily sheet entry only | (none) |

### ~40 Permissions (one per route + API endpoint)

| Permission | Route / Endpoint |
|-----------|-----------------|
| `home.view` | `/home` |
| `my-flights.view` | `/MyFlights` |
| `my-flights.export` | `/MyFlightsCSV` |
| `members.list` | `/AllMembers` |
| `member.edit` | `/MemberNew` |
| `member.edit-self` | `/EditMyDetails` |
| `flights.list` | `/AllFlightsReportNew`, `/AllFlightsMobile` |
| `flights.log` | `/DailyLogSheet` |
| `bookings.view` | `/Bookings` |
| `password.change` | `/PasswordChange` |
| `spots.view` | `/Spots` |
| `tracking.view` | `/tracks`, `/tracks-list` |
| `analytics.season-trends` | `/SeasonTrends` |
| `daily-sheet.access` | `/DailySheet` |
| `daily-sheet.edit` | `/EditDailySheet` |
| `daily-sheet.start-day` | `/StartDay` |
| `self-launch.access` | `/SelfLaunchEntry` |
| `messages.send` | `/MessagingPage` |
| `messages.view` | `/SentMessages`, `/MessagesTree` |
| `groups.manage` | `/Groups`, group CRUD |
| `billing-report.view` | `/BillingReport` |
| `treasurer-report.view` | `/TreasurerReportNew3`, `/TreasurerReportNew4` |
| `charges.manage` | `/OtherCharges` |
| `tow-charges.manage` | `/TowCharges` |
| `incentive-schemes.manage` | `/IncentiveSchemes` |
| `scheme-subs.manage` | `/SubsToSchemes` |
| `engineer.view` | `/Engineer` |
| `last-flights.view` | `/last-flights-list` |
| `users.manage` | `/Users`, `/UsersList` |
| `users.invite` | `/InviteUsers` |
| `audit.view` | `/Audits` |
| `aircraft-types.manage` | `/AircraftTypes` |
| `flight-types.manage` | `/FlightTypes` |
| `launch-types.manage` | `/LaunchTypes` |
| `billing-options.manage` | `/BillingOptions` |
| `roles.manage` | `/Roles` |
| `membership-classes.manage` | `/membership_class` |
| `membership-statuses.manage` | `/membership_status` |
| `spots.manage` | `/Spots` |
| `admin.manage` | `/manage-secret-code.php`, maintenance pages |
| `analytics.dashboard` | `/Analytics` |
| `aircraft.manage` | `/AllAircraft`, `/Aircraft` |
| `flights.manage` | `/flights-list`, `/flights` |
| `organisations.manage` | `/Organisations` |
| `god.view-as` | `/ViewAs` |
| `personas.manage` | `/Personas`, `/Persona` |
| `permissions.manage` | `/Permissions`, `/Permission` |
| `api.members` | `GET /api/members` |
| `api.member-form` | `GET/POST /api/member-form` |
| `api.member-search` | `GET /api/member-search` |
| `api.members-email` | `GET /api/members-email` |
| `api.users` | `GET /api/users` |
| `api.user-form` | `GET/POST /api/user-form` |
| `api.myflights` | `GET /api/myflights` |
| `api.flights-report` | `GET /api/flights-report` |
| `api.aircraft` | `GET /api/aircraft` |
| `api.track-flights` | `GET /api/track-flights` |
| `api.favourites` | `GET/POST /api/favourites` |
| `api.analytics-data` | `GET /api/analytics-data` |
| `api.analytics-trends` | `GET /api/analytics-trends` |
| `api.daily-flights` | `GET /api/daily-flights` (already public with `org` param) |
| `api.flights` | `GET/POST /api/flights` |
| `api.texts` | `GET /api/texts` |

### Persona → Permission Mapping (matching current bitmask behavior)

The seed migration links each persona to its permissions. This replicates the existing access control exactly:

- **booking**: bookings-related permissions
- **daily-ops**: daily-sheet.*, messages.*, groups.*, self-launch.*, flights (API)
- **cfo**: billing-report.*, treasurer-report.*, charges.*, tow-charges.*, incentive-schemes.*
- **cfi**: (currently limited — appears only in combined levels)
- **engineer**: engineer.*, last-flights.*, aircraft.* (shared with admin)
- **admin**: users.*, audit.*, reference types (aircraft-types, flight-types, etc.), analytics.dashboard, spots.*
- **god**: organisations.*, view-as, personas.*, permissions.*, users.invite
- **service-user**: daily-sheet.access

Combined checks (e.g., `aircraft.manage` requires admin OR engineer OR cfi OR cfo) are implemented as `require_persona('admin','engineer','cfi','cfo')` in the page — the check function does OR logic.

---

## Session Check Flow

At login, compute a flat permissions array and store it in the session:

```php
$_SESSION['permissions'] = [
    'home.view',
    'my-flights.view',
    'daily-sheet.access',
    // ... all permissions from all user's personas
];
```

Zero database queries at runtime — every page check is just:

```php
require_auth();  // checks $_SESSION['memberid']
require_persona('daily-sheet.access');  // in_array check on $_SESSION['permissions']
```

```php
function require_persona(...$required) {
    require_auth();
    foreach ($required as $perm) {
        if (in_array($perm, $_SESSION['permissions'] ?? [])) return;
    }
    die("Not authorized");
}
```

---

## Implementation Phases

### Phase 0: Delete 11 dead files

| File | Route to remove |
|------|----------------|
| `MessagingPageOld.php` | `/MessagingPageOld` |
| `texts-list.php` | `/texts-list-old` |
| `texts-list-last-200.php` | — |
| `message-delete.php` | — |
| `edit-my-details.php` | — |
| `api/js-debug.php` | `/api/js-debug` |
| `js/DailySheetEntry.js` | — |
| `audit.php` | — |
| `audit-list.php` | `/Audits` |
| `Treasurer2.php` | `/GlideAccounts.csv` |
| `Engineer2.php` | `/Engineering.csv` |

Also: remove associated `.htaccess` routes, update docs, remove "Old Version" links.

### Phase 1: Laravel migration — 4 new tables

`permissions`, `personas`, `persona_permissions`, `user_personas`

### Phase 2: Seed migration

Insert all 8 personas, ~60 permissions, and the persona_permission links.

### Phase 3: `helpers/permissions.php`

- `require_auth()` — checks `$_SESSION['memberid']`
- `require_persona(...$names)` — OR logic check against `$_SESSION['permissions']`
- Permission array is built at login and stored in session

### Phase 4: Permission admin pages

| Route | File | Purpose |
|-------|------|---------|
| `/Permissions` | `permissions-list.php` | List, add, delete permissions |
| `/Permission` | `permission.php` | Edit permission name + description |
| `/Personas` | `personas-list.php` | List, add, delete personas |
| `/Persona` | `persona.php` | Edit persona name + description + **permission checkboxes** |
| `/Persona/<id>` | `persona.php` | Same, with pre-checked permissions |

The persona edit page renders every permission as a checkbox grid. Checking/unchecking inserts/deletes from `persona_permissions`.

All 4 pages require `require_persona('permissions.manage','personas.manage')` (god only).

### Phase 5: User form — persona assignment

- `users-new.php`: add "Personas" section with a checkbox per persona
- `api/user-form.php`: GET returns personas list + user's assigned persona IDs; POST saves `user_personas` rows
- `users-list-v2b.php`: optionally show persona badges in DataTable

### Phase 6: Replace security checks in all pages (~30 files)

Find-and-replace per persona group. See "Current vs New" mapping above.

### Phase 7: Replace security checks in all API endpoints (~18 files)

Same pattern — API endpoints that currently check `& 1` become `require_auth()`, etc.

### Phase 8: Update auth flows

All 4 login paths load the user's permissions into `$_SESSION['permissions']`:
- `checklogin.php`
- `oauth-callback.php`
- `api/magic-link-verify.php`
- `helpers/secret_code_helpers.php` — hardcodes `daily-ops` permissions

### Phase 9: Migration SQL (deploy)

```sql
-- Assign personas by decoding the bitmask
INSERT INTO user_personas (user_id, persona_id)
SELECT u.id, p.id FROM users u, personas p
WHERE u.securitylevel & 2 AND p.name = 'booking';

INSERT INTO user_personas (user_id, persona_id)
SELECT u.id, p.id FROM users u, personas p
WHERE u.securitylevel & 4 AND p.name = 'daily-ops';

-- ... repeat for bits 8, 16, 32, 64, 128
```

This runs once at deploy time alongside the schema migration.

### Phase 10: Update secret code flow

`initiateServiceUserSession()` sets `$_SESSION['permissions']` to the equivalent of `daily-ops` persona permissions — no DB query needed, hardcoded in the helper.

### Phase 11: Update ViewAs

Replace numeric level selector with persona name selector. `home.php` interprets `$_GET['as']` as persona name. The "view as" feature loads the target persona's permissions instead of the current user's.

### Phase 12: Deploy

All changes (schema + code + migration + seed) go to production together in a single deploy. No roll-forward/roll-backward compatibility needed since this is a complete replacement.

---

## Files Changed (~95 total)

| Category | Count | Files |
|----------|-------|-------|
| Deleted | 11 | MessagingPageOld, texts-list, texts-list-last-200, message-delete, edit-my-details, api/js-debug, js/DailySheetEntry, audit, audit-list, Treasurer2, Engineer2 |
| New | 6 | permissions-list, permission, personas-list, persona, helpers/permissions.php, migration files |
| Modified | ~30 | All root PHP pages (security check line replaced) |
| Modified | ~18 | All API endpoints (security check replaced) |
| Modified | 4 | Auth flows (checklogin, oauth-callback, magic-link-verify, secret_code_helpers) |
| Modified | 3 | User pages (users-new, api/user-form, users-list-v2b) |
| Modified | 2 | ViewAs, home |
| Modified | 1 | .htaccess |
| Modified | ~6 | Docs |

---

## Testing Plan

### Approach

All tests follow the existing pattern in `tests/` — PHPUnit + GuzzleHttp making real HTTP requests to the local Vagrant dev server (`http://glidingops.test`). No mocking — tests hit the real database and verify real responses.

A `PermissionsTestCase.php` base class provides helper methods for login with different persona combinations. Individual test files extend it.

### Test Files

| Test File | What It Covers |
|-----------|---------------|
| `tests/PermissionsMigrationTest.php` | Verifies the bitmask→persona migration SQL produces correct assignments |
| `tests/PermissionsHelperTest.php` | Unit tests for `require_auth()` and `require_persona()` logic |
| `tests/PermissionsPageAccessTest.php` | 200/403 for every route under different personas |
| `tests/PermissionsApiAccessTest.php` | 200/403 for every API endpoint under different personas |
| `tests/PermissionsAuthFlowsTest.php` | Permission loading in login, OAuth, magic link, secret code |
| `tests/PermissionsAdminUITest.php` | CRUD for personas, permissions, and user→persona assignment |
| `tests/PermissionsViewAsTest.php` | ViewAs persona impersonation |
| `tests/PermissionsRegressionTest.php` | Critical pages still work (DailySheet, MyFlights, BillingReport, etc.) |

---

### `tests/PermissionsMigrationTest.php`

**Purpose:** Verify the migration SQL correctly decodes bitmasks and assigns personas.

```php
public function test_bit_1_assigns_no_persona(): void
{
    $user = $this->createUserWithSecurityLevel(1);
    $personas = $this->getUserPersonas($user['id']);
    $this->assertEmpty($personas, 'Level 1 (Member only) assigns no personas');
}

public function test_bit_4_assigns_daily_ops(): void
{
    $user = $this->createUserWithSecurityLevel(4);
    $personas = $this->getUserPersonas($user['id']);
    $this->assertContains('daily-ops', $personas);
}

public function test_bit_5_assigns_daily_ops_only(): void
{
    $user = $this->createUserWithSecurityLevel(5);  // 1+4, but 1 assigns nothing
    $personas = $this->getUserPersonas($user['id']);
    $this->assertContains('daily-ops', $personas);
    $this->assertCount(1, $personas);
}

public function test_bit_127_assigns_all_except_god(): void
{
    $user = $this->createUserWithSecurityLevel(127);
    $personas = $this->getUserPersonas($user['id']);
    $this->assertContains('daily-ops', $personas);
    $this->assertContains('cfo', $personas);
    $this->assertContains('engineer', $personas);
    $this->assertContains('admin', $personas);
    $this->assertNotContains('god', $personas);
}

public function test_bit_255_assigns_god(): void
{
    $user = $this->createUserWithSecurityLevel(255);
    $personas = $this->getUserPersonas($user['id']);
    $this->assertContains('god', $personas);
}
```

Tests for every unique level value in production (1, 5, 7, 9, 21, 33, 37, 39, 53, 63, 127, 255).

---

### `tests/PermissionsHelperTest.php`

**Purpose:** Verify `require_auth()` and `require_persona()` work correctly.

```php
public function test_require_auth_without_session_redirects(): void
{
    $response = $this->client->get('/MyFlights', ['allow_redirects' => false]);
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('/Login.php', $response->getHeaderLine('Location'));
}

public function test_require_auth_with_session_passes(): void
{
    $this->loginAs('fgordon');
    $response = $this->client->get('/MyFlights', ['allow_redirects' => false]);
    $this->assertEquals(200, $response->getStatusCode());
}

public function test_require_persona_with_wrong_persona_denies(): void
{
    $this->loginAs('fgordon');  // daily-ops only
    $response = $this->client->get('/BillingReport', ['allow_redirects' => false]);
    $this->assertEquals(403, $response->getStatusCode());
}

public function test_require_persona_with_correct_persona_allows(): void
{
    $user = $this->createUserWithSecurityLevel(8);  // CFO
    $this->loginAs($user['usercode']);
    $response = $this->client->get('/BillingReport', ['allow_redirects' => false]);
    $this->assertEquals(200, $response->getStatusCode());
}

public function test_combined_or_allows_either_persona(): void
{
    // aircraft.php allows admin OR engineer OR cfi OR cfo
    $engineer = $this->createUserWithSecurityLevel(32);
    $this->loginAs($engineer['usercode']);
    $response = $this->client->get('/AllAircraft');
    $this->assertEquals(200, $response->getStatusCode());

    $cfo = $this->createUserWithSecurityLevel(8);
    $this->loginAs($cfo['usercode']);
    $response = $this->client->get('/AllAircraft');
    $this->assertEquals(200, $response->getStatusCode());
}
```

---

### `tests/PermissionsPageAccessTest.php`

**Purpose:** Verify every page route returns expected 200/403 for each persona. Data-driven test using a provider.

```php
public static function pageAccessProvider(): array
{
    return [
        // [route, persona_or_none, expected_status]
        ['/home', 'anon', 302],           // redirects to login
        ['/home', 'member', 200],         // any authenticated user
        ['/DailySheet', 'anon', 302],
        ['/DailySheet', 'member', 403],   // auth but wrong persona
        ['/DailySheet', 'daily-ops', 200],
        ['/BillingReport', 'cfo', 200],
        ['/BillingReport', 'member', 403],
        ['/AllAircraft', 'admin', 200],
        ['/AllAircraft', 'engineer', 200],
        ['/AllAircraft', 'cfo', 200],     // OR logic
        ['/AllAircraft', 'daily-ops', 403],
        ['/Organisations', 'god', 200],
        ['/Organisations', 'admin', 403],
        ['/MessagingPage', 'daily-ops', 200],
        ['/MessagingPage', 'member', 403],
        ['/Users', 'admin', 200],
        ['/Users', 'member', 403],
        ['/Personas', 'god', 200],
        ['/Personas', 'admin', 403],
        ['/Permissions', 'god', 200],
        ['/Permissions', 'admin', 403],
        ['/ViewAs', 'god', 200],
        ['/ViewAs', 'admin', 403],
        ['/MyFlights', 'member', 200],    // auth-only
        ['/PasswordChange', 'member', 200],
        ['/EditMyDetails', 'member', 200],
        ['/Login.php', 'anon', 200],       // public
        ['/privacy', 'anon', 200],         // public
    ];
}

/** @dataProvider pageAccessProvider */
public function test_page_access(string $route, string $persona, int $expected): void
{
    if ($persona === 'anon') {
        $response = $this->client->get($route, ['allow_redirects' => false]);
    } else {
        $this->loginAsPersona($persona);
        $response = $this->client->get($route, ['allow_redirects' => false]);
    }
    $this->assertEquals($expected, $response->getStatusCode());
}
```

Also test the "Old Version" links and dead routes return 404 after Phase 0 cleanup.

---

### `tests/PermissionsApiAccessTest.php`

**Purpose:** Same as pages but for API endpoints. Tests both HTTP method (GET/POST/PUT/DELETE) and persona requirements.

```php
public static function apiAccessProvider(): array
{
    return [
        // [method, route, persona_or_none, expected_status]
        ['GET', '/api/daily-flights?org=1&date=2026-04-19', 'anon', 200],    // public
        ['GET', '/api/members', 'anon', 401],
        ['GET', '/api/members', 'member', 200],             // auth-only
        ['GET', '/api/users', 'admin', 200],
        ['GET', '/api/users', 'member', 403],
        ['POST', '/api/flights', 'daily-ops', 200],
        ['POST', '/api/flights', 'member', 403],
        ['GET', '/api/user-form', 'admin', 200],
        ['GET', '/api/user-form', 'member', 403],
    ];
}
```

Also test that the permission-checking middleware returns proper JSON error responses:

```php
public function test_api_denial_returns_json(): void
{
    $this->loginAs('fgordon');  // no admin
    $response = $this->client->get('/api/users', [
        'allow_redirects' => false,
        'http_errors' => false
    ]);
    $this->assertEquals(403, $response->getStatusCode());
    $body = json_decode($response->getBody(), true);
    $this->assertArrayHasKey('error', $body);
}
```

---

### `tests/PermissionsAuthFlowsTest.php`

**Purpose:** Verify all 4 auth paths correctly populate `$_SESSION['permissions']`.

**Password login:**
```php
public function test_password_login_loads_permissions(): void
{
    $user = $this->createUserWithSecurityLevel(8);  // CFO
    $resp = $this->client->post('/checklogin.php', [
        'form_params' => ['user' => $user['usercode'], 'pcode' => $user['password']],
        'allow_redirects' => false
    ]);
    $this->assertEquals(302, $resp->getStatusCode());

    // Now access a CFO-only page
    $resp2 = $this->client->get('/BillingReport');
    $this->assertEquals(200, $resp2->getStatusCode());
}
```

**OAuth login:** Verify OAuth callback sets permissions (mock the provider callback).

**Magic link:** Request magic link, extract token from DB (via test helper), verify, check permissions.

**Secret code:**
```php
public function test_secret_code_grants_daily_ops(): void
{
    $resp = $this->client->get('/DailySheet?org=1&key=' . TEST_SECRET_CODE);
    $this->assertEquals(200, $resp->getStatusCode());

    // Verify it does NOT grant billing access
    $resp2 = $this->client->get('/BillingReport', ['allow_redirects' => false]);
    $this->assertEquals(403, $resp2->getStatusCode());
}
```

---

### `tests/PermissionsAdminUITest.php`

**Purpose:** Verify persona and permission CRUD pages work correctly.

```php
public function test_list_permissions(): void
{
    $this->loginAsGod();
    $resp = $this->client->get('/Permissions');
    $this->assertEquals(200, $resp->getStatusCode());
    $this->assertStringContainsString('daily-sheet.access', $resp->getBody());
}

public function test_create_persona_with_permissions(): void
{
    $this->loginAsGod();
    $resp = $this->client->post('/Persona', [
        'form_params' => [
            'name' => 'test-role',
            'description' => 'Test role',
            'permissions' => ['daily-sheet.access', 'my-flights.view']
        ]
    ]);
    $this->assertEquals(302, $resp->getStatusCode());

    // Verify persona_permissions were created
    $personaId = $this->getPersonaIdByName('test-role');
    $perms = $this->getPersonaPermissions($personaId);
    $this->assertContains('daily-sheet.access', $perms);
}

public function test_assign_persona_to_user(): void
{
    $this->loginAsAdmin();
    $user = $this->createUserWithSecurityLevel(1);
    $personaId = $this->getPersonaIdByName('cfo');

    $resp = $this->client->post('/api/user-form', [
        'form_params' => [
            'id' => $user['id'],
            'personas' => [$personaId]
        ]
    ]);
    $this->assertEquals(200, $resp->getStatusCode());

    // Verify user can now access CFO pages
    $this->loginAs($user['usercode']);
    $resp2 = $this->client->get('/BillingReport');
    $this->assertEquals(200, $resp2->getStatusCode());
}
```

Also test:
- Deleting a persona removes its `persona_permissions` and `user_personas` rows
- Renaming a persona updates everywhere
- Cannot delete the last admin/god persona from yourself

---

### `tests/PermissionsViewAsTest.php`

```php
public function test_view_as_impersonates_persona(): void
{
    $this->loginAsGod();
    $resp = $this->client->get('/home?as=daily-ops');

    // Should see daily-ops home page (no billing/admin links)
    $this->assertEquals(200, $resp->getStatusCode());
    $this->assertStringNotContainsString('Billing Report', $resp->getBody());
}

public function test_view_as_non_god_rejected(): void
{
    $this->loginAs('fgordon');  // no god
    $resp = $this->client->get('/home?as=admin', ['allow_redirects' => false]);
    $this->assertEquals(403, $resp->getStatusCode());
}
```

---

### `tests/PermissionsRegressionTest.php`

**Purpose:** Verify critical workflows still work end-to-end.

```php
public function test_daily_sheet_flow(): void
{
    $this->loginAsPersona('daily-ops');
    $resp = $this->client->get('/StartDay?org=1&loc=Masterton');
    $this->assertEquals(200, $resp->getStatusCode());

    $resp = $this->client->get('/DailySheet');
    $this->assertEquals(200, $resp->getStatusCode());
}

public function test_my_flights_flow(): void
{
    $this->loginAs('fgordon');
    $resp = $this->client->get('/MyFlights');
    $this->assertEquals(200, $resp->getStatusCode());
    $this->assertStringContainsString('My Flights', $resp->getBody());
}

public function test_billing_report_flow(): void
{
    $this->loginAsPersona('cfo');
    $resp = $this->client->get('/BillingReport');
    $this->assertEquals(200, $resp->getStatusCode());
}

public function test_member_cannot_access_admin(): void
{
    $this->loginAs('fgordon');
    $resp = $this->client->get('/Users', ['allow_redirects' => false]);
    $this->assertEquals(403, $resp->getStatusCode());
}
```

---

### Test Helpers (base class)

```php
abstract class PermissionsTestCase extends TestCase
{
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new Client([
            'base_uri' => 'http://glidingops.test',
            'cookies' => true,
            'allow_redirects' => false,
            'http_errors' => false
        ]);
    }

    protected function loginAs(string $usercode): void
    {
        // POST to checklogin.php, session cookie stored in client
        $this->client->post('/checklogin.php', [
            'form_params' => [
                'user' => $usercode,
                'pcode' => $this->getPasswordForUser($usercode)
            ]
        ]);
    }

    protected function loginAsPersona(string $personaName): void
    {
        // Find or create a user with this persona and log in
        $user = $this->ensureUserWithPersona($personaName);
        $this->loginAs($user['usercode']);
    }

    protected function loginAsGod(): void
    {
        $this->loginAsPersona('god');
    }

    protected function loginAsAdmin(): void
    {
        $this->loginAsPersona('admin');
    }

    protected function createUserWithSecurityLevel(int $level): array
    {
        // Create a test user with given security level, return user data
    }

    protected function getUserPersonas(int $userId): array
    {
        // Query user_personas JOIN personas for this user
    }

    protected function getPersonaIdByName(string $name): int
    {
        // Query personas table
    }
}
```

---

### Running Tests

```bash
# Run all permission tests
cd lrv; vagrant ssh -c "cd /home/vagrant/code && ./lrv/vendor/bin/phpunit tests/Permissions*"

# Run individual test files
cd lrv; vagrant ssh -c "cd /home/vagrant/code && ./lrv/vendor/bin/phpunit tests/PermissionsPageAccessTest.php"

# Run alongside existing tests to check for regressions
cd lrv; vagrant ssh -c "cd /home/vagrant/code && ./lrv/vendor/bin/phpunit"
```

### Test Data

Tests create and clean up their own test users in the database. No production data is modified. The `setUp()` method runs within a transaction that's rolled back in `tearDown()`, or creates named test users that are dropped at the end of the suite.

A dedicated test persona (`test-runner`) with all permissions is created in the seed migration for use by the test suite.

### Acceptance Criteria

- All ~60 route×persona combinations in the page access matrix return correct status codes
- All ~30 API endpoint×persona combinations return correct status codes
- All 4 auth flows correctly populate `$_SESSION['permissions']`
- Secret code grants `daily-ops` access and nothing else
- Persona CRUD works end-to-end (create, read, update, delete)
- User→persona assignment works from user form
- Migration SQL correctly assigns all users based on existing bitmasks
- ViewAs persona impersonation works for god users, rejected for others
- All existing features (flight entry, billing report, map) still work
- Zero regressions in the existing test suite
