# Error Pages

## Overview

Centralised, branded error pages replace bare `die()` calls and Apache's default error pages. Four changes were made:

1. **Login error redirect** — wrong credentials now redirect back to `Login.php` with an inline error, instead of rendering a bare page
2. **Error page** — a standalone branded page (`error-page.php`) with no DB/session dependencies, served for HTTP 400/403/404/500/503
3. **Auth gate redirect** — `require_perm()` sends unauthorized users to the branded 403 page instead of `die("Not authorized")`
4. **Fatal error handler** — promoted to production, logs the error and redirects to branded 500 page

---

## Files Changed

| File | Change |
|------|--------|
| `error-page.php` | **New** — branded error page for 400/403/404/500/503 |
| `.htaccess` | Added 5 `ErrorDocument` directives |
| `checklogin.php` | Wrong password redirects to `Login.php?error=wrong_password` instead of bare echo |
| `Login.php` | Added `wrong_password` case to the error handler |
| `helpers/permissions.php` | `require_perm()` failure now redirects to `error-page.php?code=403` with `http_response_code(403)` |
| `helpers/error_message.php` | Rewritten to use Bootstrap `.alert-success` / `.alert-danger` classes instead of inline styles |
| `helpers/logging.php` | Fatal error handler promoted to all environments (was dev-only), logs to `log/error.log` + `error_log()`, redirects to `error-page.php?code=500` if headers not sent |

---

## Architecture

### error-page.php

Standalone PHP file with no database or session dependencies. It works even when MySQL is down, the session is broken, or the filesystem is degraded.

- **Design:** Matches the Login.php brand (dark blue `#063552` header, orange `#f26120` accent)
- **HTTP status codes handled:** 400, 403, 404, 500, 503
- **Unknown codes:** Default to 500
- **Logging:** Every error is logged via `error_log()` with URI, referrer, and user-agent
- **Security:** No sensitive info (file paths, SQL errors, stack traces) is exposed to the user

### Error path decision tree

```
Request arrives
  |
  ├── Apache 404/403/500 → ErrorDocument directive → error-page.php?code=X
  |
  ├── PHP fatal error (E_ERROR) → fatalShutdownHandler() in logging.php
  |     ├── Log to log/error.log + error_log()
  |     └── If headers not sent → redirect to error-page.php?code=500
  |
  ├── require_perm() fails
  |     ├── JSON request → {"error": "Not authorized"} with HTTP 403
  |     └── HTML request → redirect to error-page.php?code=403
  |
  ├── require_auth() fails → redirect to Login.php
  |
  ├── checklogin.php wrong password → redirect to Login.php?error=wrong_password
  |
  └── API error handler (api-base.php) → JSON 500 with error message
```

---

## Error Codes

| Code | Title | When It Appears |
|------|-------|-----------------|
| 400 | Bad Request | Malformed request, missing required params |
| 403 | Access Denied | `require_perm()` fails (user lacks permission) |
| 404 | Page Not Found | URL does not match any route or file |
| 500 | Server Error | PHP fatal error, database connection failure, uncaught exception |
| 503 | Service Unavailable | Maintenance mode active (`maintenance.trigger` file exists) |

---

## Adding a New Error Code

1. Add the entry to the `$codes` array in `error-page.php`
2. Add an `ErrorDocument` directive in `.htaccess`
3. Update this table

---

## Production Fatal Error Handling

The fatal error handler in `helpers/logging.php` runs in all environments:

```php
register_shutdown_function('fatalShutdownHandler');
```

It catches `E_ERROR`, `E_CORE_ERROR`, `E_COMPILE_ERROR`, and `E_PARSE`. On detection:
1. Logs the error to `log/error.log` (appends the line to the file)
2. Logs via `error_log()` (goes to the system log)
3. If HTTP headers haven't been sent yet, redirects the user to the branded 500 page

**Why this matters:** Previously, fatal errors on production produced a white screen of death with no user feedback and no log entry in `log/error.log` (the handler was dev-only). Now every fatal error is logged and the user sees a helpful branded page.

---

## Bootstrap Alert Classes

`helpers/error_message.php` now renders Bootstrap alert classes instead of inline styles:

| Old | New |
|-----|-----|
| `<p style="color:green"> Success! </p>` | `<div class="alert alert-success">...</div>` |
| `<p style="color:red"> Error: ...</p>` | `<div class="alert alert-danger">...</div>` |

This depends on Bootstrap 3.3.7 CSS being loaded on the page (which it is on all pages via `jsLibraies.php` or the heading template).
