# Development Workflow

## Log Locations

- **Local debug log**: `C:\Users\bruno\dev\glidingops\log\app.log`
- **Local error log**: `C:\Users\bruno\dev\glidingops\log\error.log`
- Read these directly with the `Read` tool - NO need for vagrant ssh

## PHP Syntax Check (Vagrant)

```bash
cd C:\Users\bruno\dev\glidingops\lrv; vagrant ssh -c "php -l ./code/<path>" 2>&1
```
Ignore vagrant warning about homestead - actual PHP output follows.

## Testing APIs with PowerShell

### Login and Test API (PowerShell)

Create a script file `test_api.ps1`:
```powershell
$loginResp = Invoke-WebRequest -Uri 'http://glidingops.test/checklogin.php' -Method POST -Body @{user='[dev-creds]'; pcode='[dev-creds]'} -SessionVariable ws
$apiResp = Invoke-WebRequest -Uri 'http://glidingops.test/api/members-email.php?search=bru' -WebSession $ws
$apiResp.Content
```

Run with:
```powershell
powershell -File C:\Users\bruno\dev\glidingops\test_api.ps1
```

### Check Error Log (PowerShell)

```powershell
Get-Content C:\Users\bruno\dev\glidingops\log\error.log -Tail 20
```

## Error Handling

- `helpers/logging.php` registers a fatal error handler that writes to `log/error.log` in dev
- `helpers/api-base.php` sets up an error handler for API endpoints that returns JSON

## API Endpoints

All API endpoints (`api/*.php`) MUST:
- Include `helpers/api-base.php` at top (before session_start)
- Use `header('Content-Type: application/json')` before any output
- Use `apiExit($con)` instead of `exit` to flush output buffers
- Log all requests: `logMsg("START method=" . $_SERVER['REQUEST_METHOD'])`

```php
<?php
require_once __DIR__ . '/../helpers/api-base.php';

session_start();

if (!isset($_SESSION['memberid'])) {
    apiExitWithError('Not logged in');
}
```

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

## Code Style

- Use ASCII characters only in source code - no unicode, em-dashes, smart quotes, etc.
- Keep strings simple to avoid encoding issues
- This applies to PHP, JavaScript, CSS, and any other code files
