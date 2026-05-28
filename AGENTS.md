# Glidingops Development Context

## Quick Reference

- **Dev:** http://glidingops.test | User: [dev-creds] / Password: [dev-creds]
- **Logs:** `log/app.log` (debug), `log/error.log` (PHP errors)
- **PHP check:** `vagrant ssh -c "php -l ./code/<path>" 2>&1` from `lrv` folder

## Current Work

### In Progress
- (none)

### Completed This Session
- **Billing report (billing-report.php)**: New Treasurer Monthly Billing Report at `/BillingReport`. Replaces broken Treasurer.php. Uses `helpers/billing-calc.php` for correct calculations (glider $2.25/min, Youth $1.50/min on GGR/GPJ/GMB, winch first $39 relaunch $25, self-launch $25). Collapsible member rows with expand/collapse all toggle. CSV export at `/BillingReport.csv`. Test file: `tests/BillingReportTest.php` (41 tests).
- **Column widths**: Auto-layout with `col-narrow` (width:1px; white-space:nowrap) on fixed-content columns; Member takes remaining space.

### Next Steps
1. Fix mailing list email addresses in MessagingPage.php (replace `soar.co.nz` placeholders)
2. Test messaging page end-to-end
3. Delete old files after testing: texts-list.php, users-list.php, users.php, members-list.php, members.php, MessagingPageOld.php

## How To Work In This Repo

**BEFORE starting any task:**
1. Read this AGENTS.md file (mandatory)
2. Recall past context: `agentmemory_memory_recall query="glidingops"` or `agentmemory_memory_smart_search query="glidingops"`
3. Check logs: `Get-Content log/app.log -Tail 30` and `Get-Content log/error.log -Tail 30`
4. Run `git status`

**BEFORE CLOSING a session:**
Run `agentmemory_memory_consolidate` to persist learned context across sessions.

**BUILDING NEW FEATURES:**
1. Build ex novo - do NOT adapt old code
2. Members search is primary recipient selection
3. Hardcode Google Groups mailing lists (not dynamic)
4. Save messages to DB for history + Fake Twitter
5. Send emails synchronously with real-time progress
6. Always add new API routes to .htaccess
7. Use ASCII only - no unicode or special characters in code

**TESTING:** 
- Always verify CSS/JS changes by fetching the page with PowerShell WebRequestSession (see `docs/WEB_AUTH.md`). Do NOT rely purely on code reasoning — the browser is the ground truth.
- Use `docs/WEB_AUTH.md` for PowerShell WebRequestSession patterns
- Run PHPUnit test suite: `cd lrv; vagrant ssh -c "cd /home/vagrant/code && ./lrv/vendor/bin/phpunit"` (see `docs/TESTING.md` for full details)

**BEFORE DELETING OLD FILES:** See `docs/DEAD_CODE.md`

## Key Reference

See these documents for detailed info:
- `docs/DEVELOP.md` - Dev workflow, logging, API patterns, session vars, database conventions, code style
- `docs/DEAD_CODE.md` - Files marked for deletion
- `docs/TODO.md` - Known issues
- `docs/ARCHITECTURE.md` - Technical structure

## Documentation Index

All docs in `docs/` folder - see individual files for details:
- README.md, ARCHITECTURE.md, DATABASE.md, FEATURES.md, ROUTES.md, SECURITY.md, MESSAGING.md, CODEBASE_MAP.md, WEB_AUTH.md, TESTING.md, DEVELOP.md, DEAD_CODE.md, TODO.md

## Dev URLs
- **App:** http://glidingops.test (NOT https - self-signed cert issues)
- **Login:** Username: [dev-creds] / Password: [dev-creds]

## Local Logs (Read directly - NO vagrant ssh needed)
- `log/app.log` - Debug log via `logMsg()`
- `log/error.log` - PHP errors (fatal handler in `helpers/logging.php`)

## Key Files
- `helpers/api-base.php` - API error handling, `apiExit()`, `apiExitWithError()`
- `helpers/logging.php` - `logMsg()`, `isLocal()`, fatal error handler
- `config/database.php` - gliding and tracks DB config

## Session Variables
- `$_SESSION['memberid']`
- `$_SESSION['security']` - bitmask (1=member, 6=admin)
- `$_SESSION['org']`

## Database Conventions
- Primary key is `id` (NOT `member_id`) in members table
- Membership status column is `status_name` (NOT `status`)
- Connection from `config/database.php` => `['gliding']`
- Always use `intval()` for integer values in SQL

## API Development Rules
1. **Call `session_start()` FIRST** before any output
2. **Use `apiExit()` NOT `exit()`** - flushes output buffers
3. **Use `apiExitWithError()` for errors** - returns JSON with error message
4. **Use `header('Content-Type: application/json')** before any echo
5. **Echo JSON BEFORE calling apiExit()** - pattern: `header(...); echo json_encode([...]); apiExit($con);`
6. **Use `__DIR__ . '/../config/database.php'`** NOT `./config/database.php`
7. **Always log with `logMsg()`** to track execution
8. **Add route to `.htaccess`** - `RewriteRule ^api/NAME$ api/NAME.php [L,QSA]`
9. **Never make breaking API changes** — external scripts (see `docs/DEPENDENCIES.md`) consume API endpoints. Fields must not be removed or renamed. New fields must be additive and optional.
10. **Include helpers.php** if using helper functions like `getTowLaunchType()`, `getGlidingFlightType()`

## Route Order in .htaccess
More specific routes must come BEFORE less specific ones.
For example:
- `BillingReport` before `BillingOptions`
- `MyFlightsCSV` before `MyFlights`

## Modernized Pages (v2b pattern)
All new list pages use DataTables server-side AJAX:
- `members-list-v2b.php`
- `users-list-v2b.php`
- `texts-list-v2b.php`

## Testing APIs with PowerShell
See `docs/WEB_AUTH.md` for PowerShell scripts to:
- Login with session cookie
- Test authenticated API endpoints

## PHP Syntax Check (Vagrant)
```bash
cd lrv; vagrant ssh -c "php -l ./code/<path>" 2>&1
```

## Running Tests
```bash
cd lrv; vagrant ssh -c "cd /home/vagrant/code && ./lrv/vendor/bin/phpunit"
```
```

Test suite in `tests/` using PHPUnit + GuzzleHttp:
- `HomePageTest` — CSS Grid, widgets, height/limit, DOM structure
- `PhotoUploadTest` — Upload photo with GD resize, reject non-image
- `MemberListPhotoTest` — Photo URLs are ID-based, not displayname-based
- `HeaderPhotoTest` — Header photo on home, AllMembers, MyFlights, noprofile fallback
- `NavigationTest` — 45+ internal links return 200, specific expected links present