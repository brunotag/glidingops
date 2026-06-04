# Glidingops Development Context

## Quick Reference

- **Dev:** http://glidingops.test | User: [dev-creds] / Password: [dev-creds]
- **Logs:** `log/app.log` (debug), `log/error.log` (PHP errors)
- **PHP check:** `vagrant ssh -c "php -l ./code/<path>" 2>&1` from `lrv` folder

## Current Work

### In Progress
- (none)

### Completed This Session
- **Migrated auth from bitmask ($_SESSION['security'] & N) to permission-based system** (`require_perm('perm.name')`). Core: `helpers/permissions.php`. 9 personas, 66 permissions. All page-level, API-level, and home-page widget checks migrated. `compute_security_bitmask()`, `$personaBitmask`, `$_SESSION['personas']`, `helpers/session_helpers.php` deleted. Permission-subset assignment (editor can only assign personas whose perms are a subset of editor's). Secret code flow preserved. ViewAs uses per-request override (no session corruption). Member persona auto-assigned to all users.
- Deployed to production commit `e9897e3`.

### Next Steps
1. Fix mailing list email addresses in MessagingPage.php (replace `soar.co.nz` placeholders)
2. Test messaging page end-to-end
3. Delete old files after testing: texts-list.php, users-list.php, users.php, members-list.php, members.php, MessagingPageOld.php

## How To Work In This Repo

**BEFORE starting any task (AUTO-RUN these steps in order):**
1. Read this AGENTS.md file (mandatory)
2. Recall past context: `agentmemory_memory_recall query="glidingops"` — this loads all past decisions, architecture, and lessons learned from previous sessions. Always do this first before any code changes.
3. Check logs: `Get-Content log/app.log -Tail 30` and `Get-Content log/error.log -Tail 30`
4. Run `git status`

## CRITICAL RULES

**NEVER write to production without explicit confirmation.** Before pushing, deploying, or running any command on the production server (SSH, git pull, file writes, etc.), ask the user first. "Fix it" means fix locally unless they explicitly say "deploy" or "push to production."

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

**DEPLOYMENT:**
1. `git pull` on production (SSH credentials in `docs/_secrets.md`)
2. `cd /var/www/html/lrv && php artisan migrate --force`
3. Clear session files: `rm -f /var/lib/php/sessions/*`
4. Restart Apache: `systemctl restart apache2`
5. Note: Apache restart alone does NOT clear sessions — files on disk persist. Must explicitly delete session files.

**AUTHORIZATION (new system):**
- ALL auth gates use `require_perm('perm.name')` — never `$_SESSION['security']`, never bitmasks, never persona names
- `helpers/permissions.php` is the single source of truth
- `require_auth()` has a service-user bypass: `$_SESSION['who'] === 'service-user'` passes even though `userid = -1`
- `effective_permissions()` resolves the `?as=` override per-request (no session corruption)
- `getAssignablePersonaIds()` uses permission-subset matching: editor can only assign personas whose perms are a subset of editor's own

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
- `helpers/permissions.php` - Permission system: `require_perm()`, `has_perm()`, `effective_permissions()`
- `config/database.php` - gliding and tracks DB config

## Session Variables
- `$_SESSION['memberid']`
- `$_SESSION['org']`
- `$_SESSION['permissions']` — array of permission strings, resolved from user_personas at login (replaces old `$_SESSION['security']` bitmask)
- `$_SESSION['security']` — **REMOVED** as of commit `e9897e3`. All auth uses `require_perm()` now.

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