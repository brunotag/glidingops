# Glidingops Development Context

## Quick Reference

### Dev URLs
- **App:** http://glidingops.test (NOT https — self-signed cert issues)
- **Login:** Username: [dev-creds] / Password: [dev-creds]

### Log Locations
- `log/app.log` — Debug log via `logMsg()`
- `log/error.log` — PHP errors (fatal handler in `helpers/logging.php`)

### PHP Syntax Check
From `lrv` subfolder: `vagrant ssh -c "php -l ./code/<path>" 2>&1`

## Current Work

### In Progress
- **Better Messaging v2** — `MessagingPage.php` built ex novo with streaming progress UI
  - Mailing list email addresses still need fixing (placeholder `soar.co.nz` domain)
  - Needs end-to-end testing

### Completed This Session
- TreasurerReportNew.php, TreasurerReportNew2.php, TreasurerReportNew3.php (3 layouts)
- Added Treasurer reports to home page (security bit 8)
- `api/members-email.php` for member autocomplete
- `apiMaybeResumeSession()` + `apiRequireAuth()` added to helpers/api-base.php
- MessagingPage.php ex novo with two-panel layout, member search, mailing lists, preview modal, streaming progress, result modal

### Next Steps (Priority Order)
1. Fix mailing list email addresses in MessagingPage.php (replace `soar.co.nz` placeholders)
2. Test messaging page end-to-end
3. Delete old files after testing: texts-list.php, users-list.php, users.php, members-list.php, members.php
4. Magic link passwordless login (docs/FUTURE_DEVELOPMENT_MAGIC_LINK.md)

## How To Work In This Repo

**BEFORE starting any task:**
1. Read this AGENTS.md file (mandatory)
2. Check log/app.log for recent activity: `Get-Content log/app.log -Tail 30`
3. Check log/error.log for errors: `Get-Content log/error.log -Tail 30`
4. Run `git status` to see what's changed

**WHEN BUILDING NEW FEATURES:**
1. Build ex novo — do NOT adapt old code
2. Keep members search as primary recipient selection
3. Hardcode Google Groups mailing lists (don't make dynamic)
4. Save messages to DB for history + Fake Twitter
5. Send emails synchronously with real-time progress feedback
6. Always add to .htaccess when creating new API routes

**AFTER MAKING CHANGES:**
1. Run PHP syntax check: `vagrant ssh -c "php -l ./code/<path>"`
2. Check log/app.log to confirm expected behavior
3. Check log/error.log for any errors

**TESTING:** See `docs/WEB_AUTH.md` for PowerShell testing with WebRequestSession

**BEFORE DELETING OLD FILES:** See `docs/DEAD_CODE.md` for files marked for deletion and cleanup steps

**WHEN STUCK OR UNSURE:**
- docs/DEAD_CODE.md — context on old files
- docs/TODO.md — known issues
- docs/ARCHITECTURE.md — technical structure
- Ask for clarification if blocked for more than 5 minutes

## Key Reference

### Session Variables
- `$_SESSION['memberid']`
- `$_SESSION['security']` — bitmask (1=member, 6=admin)
- `$_SESSION['org']`

### Database Conventions
- Primary key is `id` (NOT `member_id`) in members table
- Connection from `config/database.php` → `['gliding']`
- Always use `intval()` for integer values in SQL
- members.status is INT FK to membership_status.id (not status_name string)
- **ALWAYS verify schema first** — see `docs/DEVELOP.md`

### API Development
- See `docs/DEVELOP.md` for API patterns, auth, and error handling
- Use `helpers/api-base.php` functions: `apiMaybeResumeSession()`, `apiRequireAuth()`, `apiExit()`, `apiExitWithError()`

### Modernized List Pages (v2b pattern)
All new list pages: DataTables server-side AJAX, auto-trigger search, "Old Version" link points to original.

Files: `members-list-v2b.php`, `users-list-v2b.php`, `texts-list-v2b.php`

## Documentation

All docs in `docs/` folder:
- `README.md` (root) — Entry point, overview and navigation
- `docs/ARCHITECTURE.md` — Technical structure, two databases, tracking flow
- `docs/DATABASE.md` — Tables, schemas, relationships
- `docs/FEATURES.md` — What each feature does
- `docs/ROUTES.md` — URL routing via .htaccess
- `docs/SECURITY.md` — Auth, permissions, roles
- `docs/MESSAGING.md` — Email/SMS system
- `docs/CODEBASE_MAP.md` — File organization
- `docs/WEB_AUTH.md` — Session handling with WebRequestSession, API header auth
- `docs/DEVELOP.md` — Dev workflow, logging, API patterns, boilerplate
- `docs/DEAD_CODE.md` — Files marked for deletion
- `docs/TODO.md` — Known issues and planned work
- `docs/FUTURE_DEVELOPMENT_MAGIC_LINK.md` — Passwordless email login spec