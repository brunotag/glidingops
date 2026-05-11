# Glidingops Development Context

## Quick Reference

- **Dev:** http://glidingops.test | User: [dev-creds] / Password: [dev-creds]
- **Logs:** `log/app.log` (debug), `log/error.log` (PHP errors)
- **PHP check:** `vagrant ssh -c "php -l ./code/<path>" 2>&1` from `lrv` folder

## Current Work

### In Progress
- (none)

### Completed This Session
- TreasurerReportNew.php, TreasurerReportNew2.php, TreasurerReportNew3.php (3 layouts)
- Added Treasurer reports to home page (security bit 8)
- `api/members-email.php` for member autocomplete
- `apiMaybeResumeSession()` + `apiRequireAuth()` added to helpers/api-base.php
- MessagingPage.php ex novo with two-panel layout, member search, mailing lists, preview modal, streaming progress, result modal
- Restored old MessagingPage as MessagingPageOld.php (from commit f436d39)

### Next Steps
1. Fix mailing list email addresses in MessagingPage.php (replace `soar.co.nz` placeholders)
2. Test messaging page end-to-end
3. Delete old files after testing: texts-list.php, users-list.php, users.php, members-list.php, members.php, MessagingPageOld.php
4. Magic link passwordless login (docs/FUTURE_DEVELOPMENT_MAGIC_LINK.md)

## How To Work In This Repo

**BEFORE starting any task:**
1. Read this AGENTS.md file (mandatory)
2. Check logs: `Get-Content log/app.log -Tail 30` and `Get-Content log/error.log -Tail 30`
3. Run `git status`

**BUILDING NEW FEATURES:**
1. Build ex novo - do NOT adapt old code
2. Members search is primary recipient selection
3. Hardcode Google Groups mailing lists (not dynamic)
4. Save messages to DB for history + Fake Twitter
5. Send emails synchronously with real-time progress
6. Always add new API routes to .htaccess
7. Use ASCII only - no unicode or special characters in code

**TESTING:** See `docs/WEB_AUTH.md` for PowerShell WebRequestSession patterns

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