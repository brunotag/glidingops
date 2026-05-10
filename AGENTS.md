# Glidingops Development Context

## Quick Reference

### Dev URLs
- **App:** http://glidingops.test (NOT https — self-signed cert issues)
- **Login:** Username: [dev-creds] / Password: [dev-creds]
- **Local logs:** `C:\Users\bruno\dev\glidingops\log\`

### PHP Syntax Check (Vagrant)

From the `lrv` subfolder, run:
```
vagrant ssh -c "php -l ./code/<path>" 2>&1
```
Ignore the Homestead warning — actual PHP output follows after.

### Log Locations (Read directly — NO vagrant ssh needed)
- `log/app.log` — Debug log via `logMsg()`
- `log/error.log` — PHP errors (fatal handler in `helpers/logging.php`)

### API Development Rules
1. **Call `session_start()` FIRST** before any output
2. **Use `apiExit()` NOT `apiExitWithError()`** — that function doesn't exist!
3. **Use `header('Content-Type: application/json')` before any echo
4. **Use `__DIR__ . '/../config/database.php'`** NOT `./config/database.php`
5. **Always log with `logMsg()`** to track execution
6. **Add route to `.htaccess`** — `RewriteRule ^api/NAME$ api/NAME.php [L,QSA]`
7. **For errors before DB connection, use `apiExit()` without `$con`**

### Key Files
- `helpers/api-base.php` — API error handling, `apiExit()`
- `helpers/logging.php` — `logMsg()`, `isLocal()`, fatal error handler
- `config/database.php` — gliding and tracks DB config

### Session Variables
- `$_SESSION['memberid']`
- `$_SESSION['security']` — bitmask (1=member, 6=admin)
- `$_SESSION['org']`

### Database Conventions
- Primary key is `id` (NOT `member_id`) in members table
- Membership status column is `status_name` (NOT `status`)
- Connection from `config/database.php` → `['gliding']`
- Always use `intval()` for integer values in SQL

### Modernized List Pages (v2b pattern)
All new list pages use the same pattern:
- DataTables server-side AJAX
- Search with auto-trigger on type (debounce 500ms after 2+ chars)
- Pagination in controls bar
- "Old Version" link points to original .php file

Files:
- `members-list-v2b.php` — Members list
- `users-list-v2b.php` — Users list
- `texts-list-v2b.php` — Messages/texts list

## Documentation (start at README.md)

All docs in `docs/` folder:
- `README.md` (root) — Entry point, overview and navigation
- `docs/ARCHITECTURE.md` — Technical structure, two databases, tracking flow, org customization
- `docs/DATABASE.md` — Tables, schemas, relationships
- `docs/FEATURES.md` — What each feature does
- `docs/ROUTES.md` — URL routing via .htaccess
- `docs/SECURITY.md` — Auth, permissions, roles
- `docs/MESSAGING.md` — Email/SMS system
- `docs/CODEBASE_MAP.md` — File organization
- `docs/WEB_AUTH.md` — Session handling with WebRequestSession
- `docs/DEVELOP.md` — Dev workflow, logging, API patterns
- `docs/DEAD_CODE.md` — Files marked for deletion, links to delete before deletion
- `docs/TODO.md` — Known issues and planned work
- `docs/FUTURE_DEVELOPMENT_MAGIC_LINK.md` — Passwordless email login spec