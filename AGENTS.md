# Glidingops Development Context

## Quick Reference

- **Dev:** http://glidingops.test | User: [dev-creds] / Password: [dev-creds]
- **Logs:** `log/app.log` (debug), `log/error.log` (PHP errors)
- **PHP check:** `vagrant ssh -c "php -l ./code/<path>" 2>&1` from `lrv` folder

## Current Work

### In Progress
- (none)

### Completed This Session
- members-list-v2b.php + api/members.php: New "User" column with link to user account or "No"
- users-list-v2b.php + api/users.php: New "Last Login" column sortable by most recent login timestamp
- Login.php completely rewritten: Bootstrap card layout, tabbed UI (Password + Email or Register), mobile-responsive
- api/magic-link-request.php: New endpoint generates 64-char token, sends magic link email. Auto-creates user account if member email entered but no user exists. Rate-limited to 3 unused tokens per user.
- api/magic-link-verify.php: New endpoint validates token (exists, unused, 15-min expiry), creates session, sets auth_via_magic_link flag, redirects to PasswordChange
- PasswordChange.php + changepw.php: Skip old-password check when auth_via_magic_link is set, show explanatory banner. PasswordChange.php modernised with Bootstrap card layout.
- magic_link_tokens table created via Laravel migration
- Forgotten.php deleted (replaced by magic link flow)
- Register.php deleted (absorbed into Email or Register tab)
- .htaccess updated with magic link routes
- MessagingPage.php security changed from Level 1 to Level 5
- home.php completely rewritten: Dashboard layout with 3 data widgets (My Gliding, Flying/Tracking, Latest Updates) + navigation cards in priority order. CSS column-width: 280px for responsive masonry. Color scheme unified (#063552 backgrounds, #f26120 text on dark, white hover).
- MyFlights.php: Date column sortable (asc/desc) with arrow indicator
- Color scheme overhaul across all CSS files: #000040, #000080, #0000FF replaced with #063552. Foreground on dark backgrounds changed to #f26120.
- docs updated: SECURITY.md, DEPLOY.md, ROUTES.md, FEATURES.md, AGENTS.md
- FUTURE_DEVELOPMENT_MAGIC_LINK.md removed (now reality)

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

**BEFORE DELETING OLD FILES:** See `docs/DEAD_CODE.md`

## Key Reference

See these documents for detailed info:
- `docs/DEVELOP.md` - Dev workflow, logging, API patterns, session vars, database conventions, code style
- `docs/DEAD_CODE.md` - Files marked for deletion
- `docs/TODO.md` - Known issues
- `docs/ARCHITECTURE.md` - Technical structure

## Documentation Index

All docs in `docs/` folder - see individual files for details:
- README.md, ARCHITECTURE.md, DATABASE.md, FEATURES.md, ROUTES.md, SECURITY.md, MESSAGING.md, CODEBASE_MAP.md, WEB_AUTH.md, DEVELOP.md, DEAD_CODE.md, TODO.md, FUTURE_DEVELOPMENT_MAGIC_LINK.md

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
5. **Use `__DIR__ . '/../config/database.php'`** NOT `./config/database.php`
6. **Always log with `logMsg()`** to track execution
7. **Add route to `.htaccess`** - `RewriteRule ^api/NAME$ api/NAME.php [L,QSA]`

## Route Order in .htaccess
More specific routes must come BEFORE less specific ones.
For example:
- `TreasurerReportNew2` before `TreasurerReportNew`
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
cd C:\Users\bruno\dev\glidingops\lrv; vagrant ssh -c "php -l ./code/<path>" 2>&1
```