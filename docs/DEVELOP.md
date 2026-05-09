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

require_once __DIR__ . '/../helpers/logging.php';
logMsg("START method=" . $_SERVER['REQUEST_METHOD']);
```

## SQL Safety

- Always use `intval()` for integer values in SQL
- Always check `mysqli_query()` result before using it with `mysqli_fetch_assoc()`
- Column name for status is `status_name`, not `status`

## Session Variables

- `$_SESSION['memberid']`
- `$_SESSION['security']` - bitmask (1=member, 6=admin)
- `$_SESSION['org']`

## Database

- Primary key is `id` (NOT `member_id`) in members table
- Connection from `config/database.php` → `['gliding']`
- membership_status column is `status_name` (NOT `status`)
