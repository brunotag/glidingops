# Development Workflow

## Log Locations

- **Local debug log**: `log/app.log`
- **Local error log**: `log/error.log`
- Read these directly with the `Read` tool - NO need for vagrant ssh

## PHP Syntax Check (Vagrant)

```bash
cd lrv; vagrant ssh -c "php -l ./code/<path>" 2>&1
```
Ignore vagrant warning about homestead - actual PHP output follows.

## Running Tests

The project uses PHPUnit + GuzzleHttp for end-to-end tests (see [TESTING.md](TESTING.md) for full details).

```bash
cd lrv; vagrant ssh -c "cd /home/vagrant/code && ./lrv/vendor/bin/phpunit"
```

## Testing APIs with PowerShell

### Login and Test API (PowerShell)

Create a script file `test_api.ps1`:
```powershell
$loginResp = Invoke-WebRequest -Uri 'http://glidingops.test/checklogin.php' -Method POST -Body @{user='[dev-creds-see-_secrets.md]'; pcode='[dev-creds-see-_secrets.md]'} -SessionVariable ws
$apiResp = Invoke-WebRequest -Uri 'http://glidingops.test/api/members-email.php?search=bru' -WebSession $ws
$apiResp.Content
```

Run with:
 ```powershell
 powershell -File test_api.ps1
 ```

### Check Error Log (PowerShell)

```powershell
Get-Content log/error.log -Tail 20
```

## Error Handling

- `helpers/logging.php` registers a fatal error handler (`fatalShutdownHandler()`) in all environments. Logs to `log/error.log` + `error_log()`, redirects to branded 500 page if headers not sent.
- `helpers/api-base.php` sets up an error handler for API endpoints that returns JSON
- `helpers/permissions.php` `require_perm()` sets HTTP 403 and redirects to `error-page.php?code=403`
- `error-page.php` serves branded error pages for HTTP 400/403/404/500/503 (triggered by `ErrorDocument` in `.htaccess`)
- See `docs/ERROR_PAGES.md` for full documentation

## API Endpoints

All API endpoints (`api/*.php`) MUST:
- Include `helpers/api-base.php` at top (before session_start)
- Use `header('Content-Type: application/json')` before any output
- Use `apiExit($con)` instead of `exit` to flush output buffers
- Log all requests: `logMsg("START method=" . $_SERVER['REQUEST_METHOD'])`

### 11. API Must Include All Front-End Fields
When a front-end page renders data from an API, every field it references must be in the API response.
The SQL may SELECT a column but if it's not mapped in the PHP response array, it won't reach the client.
Always check both the SQL query AND the response array (`$flights[] = [...]`).

```php
// WRONG: selects location but doesn't include it
$flights[] = ['seq' => $row['seq'], 'glider' => $row['glider']];

// RIGHT:
$flights[] = ['seq' => $row['seq'], 'glider' => $row['glider'], 'location' => $row['location']];
```

```php
<?php
require_once __DIR__ . '/../helpers/api-base.php';

session_start();

if (!isset($_SESSION['memberid'])) {
    apiExitWithError('Not logged in');
}
```

## Schema Changes (Laravel Migrations)

All database schema changes MUST use Laravel migrations in `lrv/`:

```bash
cd lrv
vagrant ssh -c "cd /home/vagrant/code/lrv && php artisan make:migration <snake_case_name>"
# Edit the generated file in lrv/database/migrations/
vagrant ssh -c "cd /home/vagrant/code/lrv && php artisan migrate"
```

Never use raw SQL for schema changes. This ensures the migration history is tracked and reproducible.

## Database Query Rules

**ALWAYS verify schema before writing queries:**
```bash
mysql gliding -e "DESCRIBE <table>;"
mysql gliding -e "SELECT * FROM <lookup_table> LIMIT 5;"
```

Never assume columns exist — check `DESCRIBE table` first. Never assume column names — verify with a SELECT.

## SQL Safety

- Always use `intval()` for integer values in SQL
- Always check `mysqli_query()` result before using it with `mysqli_fetch_assoc()`
- members.status is an INT FK to membership_status.id (not a string!)

## Session Variables

- `$_SESSION['memberid']`
- `$_SESSION['security']` - bitmask (1=member, 6=admin)
- `$_SESSION['org']`

## Database

- Primary key is `id` (NOT `member_id`) in members table
- Connection from `config/database.php` -> `['gliding']`
- membership_status column is `status_name` (NOT `status`)

## Email Testing in Dev

### Current Setup

`helpers/mail.php` uses PHPMailer over SMTP to send emails. Dev config (`config/mail.php`) points to MailHog on `localhost:1025` with no auth.

### Viewing Captured Emails

Open http://glidingops.test:8025 in a browser to see all captured emails with full content.

### How It Works

- `helpers/mail.php` creates a PHPMailer instance and connects to the configured SMTP server
- Dev: MailHog on port 1025, no auth, web UI on port 8025
- Production: `smtp.gmail.com:465` with `machinery.gops@wwgc.co.nz` app password
- Config in `config/mail.php` (gitignored), template at `config/mail.php.sample`

## Local Dev Setup After Backup Restore

Restoring a production backup locally wipes the dev user `fgordon`. Recreate it:

```bash
# 1. Check what dev user exists
vagrant ssh -c "mysql gliding -e \"SELECT id, usercode, member FROM users WHERE usercode LIKE '%gordon%';\""

# 2. If missing, create the dev user
#    (user/pass both "fgordon", MD5 hash from echo -n "fgordon" | md5sum)
vagrant ssh -c "mysql gliding -e \"INSERT INTO users (name, usercode, password, org) VALUES ('Fred Gordon', 'fgordon', '1ff17bffa21715410a5970dc06cdb0f8', 1);\""

# 3. Find your member record
vagrant ssh -c "mysql gliding -e \"SELECT id, displayname FROM members WHERE firstname LIKE '%Bruno%';\""

# 4. Link the user to the member
vagrant ssh -c "mysql gliding -e \"UPDATE users SET member = 5708 WHERE usercode = 'fgordon';\""

# 5. Verify
vagrant ssh -c "mysql gliding -e \"SELECT u.id, u.usercode, u.member, m.displayname FROM users u LEFT JOIN members m ON m.id = u.member WHERE u.usercode = 'fgordon';\""
```

The dev login credentials are in `docs/_secrets.md`.

## Code Style

- Use ASCII characters only in source code - no unicode, em-dashes, smart quotes, etc.
- Keep strings simple to avoid encoding issues
- This applies to PHP, JavaScript, CSS, and any other code files

---

## API Development Gotchas

When creating new API endpoints:

### 1. API Response Pattern
The `apiExit()` function takes a database connection, NOT data. You must echo JSON manually:

```php
header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $data]);
apiExit($con);  // Pass $con or null to close DB connection
```

**WRONG:**
```php
apiExit(['success' => true, 'data' => $data]);  // This breaks!
```

### 2. Routes Must Be Added to .htaccess
New API endpoints don't auto-route. Add manually:
```apache
RewriteRule ^api/daily-flights$ api/daily-flights.php [L,QSA]
```

### 3. Include Required Helpers
Some API functions need helpers.php for database functions:
```php
require_once __DIR__ . '/../helpers.php';
```

### 4. Session/Credentials
Browser JavaScript must send session cookie:
- Use `fetch('/api/endpoint', { credentials: 'same-origin' })`
- Or `xhr.withCredentials = true` for XMLHttpRequest
- Without this, API returns "Not logged in"
