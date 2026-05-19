---
name: glidingops-api
description: Glidingops API development - PHP syntax checks, API error handling, session handling, logging conventions
---

# Glidingops API Development Skill

## PHP Syntax Check (Vagrant)
```bash
cd lrv; vagrant ssh -c "php -l ./code/<path>" 2>&1
```
Ignore the vagrant warning about homestead - actual PHP output follows.

## API Endpoints Checklist

When creating or editing API endpoints (`api/*.php`):

1. **Top of file** - Include `helpers/api-base.php` BEFORE session_start:
```php
<?php
require_once __DIR__ . '/../helpers/api-base.php';

session_start();
```

2. **Always return JSON** - The api-base error handler catches PHP errors and returns JSON automatically

3. **Logging** - Add at start:
```php
require_once __DIR__ . '/../helpers/logging.php';
logMsg("START method=" . $_SERVER['REQUEST_METHOD']);
```

4. **Content-Type** - Add header before any output:
```php
header('Content-Type: application/json');
```

5. **Exit properly** - Use `apiExit($con)` instead of plain `exit`:
```php
mysqli_close($con);
apiExit();
```

## Key Files Reference
- `helpers/api-base.php` - Provides apiExit() and error handler
- `helpers/logging.php` - Provides logMsg() for local debug logging
- `log/app.log` - Debug log (local only, gitignored)

## Session Variables
- `$_SESSION['memberid']`
- `$_SESSION['security']` - bitmask (1=member, 6=admin)
- `$_SESSION['org']`

## Database Notes
- Primary key is `id` (NOT `member_id`) in members table
- Use mysqli with prepared statements
- Connection params from `config/database.php` → `['gliding']`
