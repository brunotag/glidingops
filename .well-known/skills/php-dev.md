---
description: PHP development patterns, helpers, database access, session handling, and API development for this gliding club application
mode: subagent
---

# PHP Development Guidelines

## Required Include Pattern

Every PHP file should start with:
```php
<?php
require_once __DIR__ . '/../helpers/api-base.php';  // for API files
require_once __DIR__ . '/../config/database.php';  // for database access
```

Use `__DIR__` - NOT relative paths like `./` - because web server working directory may vary.

## Session Management

```php
session_start();  // MUST be first, before ANY output

// Check auth
if (!isset($_SESSION['memberid'])) {
    header('Location: Login.php');
    die("Please logon");
}

// Check security level (bitmask)
if (!($_SESSION['security'] & 4)) {  // level 4 = Daily Ops
    die("Security level too low");
}
```

## Database Connection

```php
$con_params = require(__DIR__ . '/../config/database.php');
$con = new mysqli($con_params['hostname'], $con_params['username'], 
                  $con_params['password'], $con_params['database']);
```

Connection name: `$con` (not `$db` or `$mysqli`).

## API Development

For API endpoints in `api/*.php`:
```php
require_once __DIR__ . '/../helpers/api-base.php';

session_start();
apiMaybeResumeSession();   // resumes session from headers if present
apiRequireAuth();          // fails with 400 if not logged in

header('Content-Type: application/json');

// ... API logic ...

apiExit($con);  // flushes buffers, closes connection
```

Use `apiExit()` NOT `exit()` - it flushes output buffers.

## Key Helpers

| Helper | Location | Purpose |
|--------|----------|---------|
| `apiBase.php` | helpers/ | API error handling, `apiExit()`, `apiExitWithError()` |
| `logging.php` | helpers/ | `logMsg()`, `isLocal()`, fatal error handler |
| `session_helpers.php` | helpers/ | `require_security_level()`, `current_org()` |
| `timehelpers.php` | helpers/ | `orgTimezone()`, `timeLocalFormat()` |
| `audit_helpers.php` | helpers/ | `audit_log()` |

## Database Conventions

- Primary key is `id` (NOT `member_id`) in members table
- Membership status is `status_name` (NOT `status`) - FK to membership_status.id
- Always use `intval()` for integer values in SQL
- Check mysqli_query() result before using with mysqli_fetch_assoc()
- members.status is INT FK, NOT string

## Session Variables

```php
$_SESSION['memberid']   // members.id
$_SESSION['security']  // bitmask: 1=member, 4=daily ops, 8=treasurer, 64=admin
$_SESSION['org']       // organisations.id (0 for admin)
$_SESSION['userid']     // users.id
$_SESSION['who']        // usercode (username/email)
```

## Security Bitmask

| Level | Value | Name |
|-------|-------|------|
| 1 | 1 | Member |
| 4 | 4 | Daily Ops |
| 8 | 8 | CFO/Treasurer |
| 32 | 32 | Engineer |
| 64 | 64 | Admin |

Check: `$_SESSION['security'] & LEVEL` (non-zero = has access)

## Code Style

- Use ASCII only - no unicode, em-dashes, smart quotes in source code
- Keep strings simple to avoid encoding issues
- Bootstrap 3.3.7 + jQuery 1.12.4 loaded via jsLibraies.php

## PHP Syntax Check (Vagrant)

```bash
cd C:\Users\bruno\dev\glidingops\lrv; vagrant ssh -c "php -l ./code/<path>" 2>&1
```

## Error Logging

- Local debug log: `C:\Users\bruno\dev\glidingops\log\app.log`
- Local error log: `C:\Users\bruno\dev\glidingops\log\error.log`
- helpers/logging.php registers fatal error handler
- API errors go to error.log with JSON response