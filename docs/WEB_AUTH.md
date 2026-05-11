# Web Authentication Testing

## How to Authenticate and Test Pages with Session

Use PowerShell's WebRequestSession to maintain cookies across requests.

### PowerShell Script Template

```powershell
# Step 1: Get login page and capture session
$login = Invoke-WebRequest -Uri 'http://glidingops.test/Login.php' -UseBasicParsing -SessionVariable 'webSession'

# Step 2: Submit login form (username: [dev-creds], password: [dev-creds])
$body = "user=[dev-creds]&pcode=[dev-creds]"
$loginResult = Invoke-WebRequest -Uri 'http://glidingops.test/checklogin.php' -WebSession $webSession -Method POST -Body $body -ContentType "application/x-www-form-urlencoded" -UseBasicParsing -MaximumRedirection 10

# Step 3: Visit home to ensure session is established
$homeResult = Invoke-WebRequest -Uri 'http://glidingops.test/home' -WebSession $webSession -UseBasicParsing

# Step 4: Now you can access authenticated pages
$page = Invoke-WebRequest -Uri 'http://glidingops.test/TargetPage' -WebSession $webSession -UseBasicParsing
Write-Host $page.Content
```

### Testing API Endpoints

```powershell
# Login first
$body = "user=[dev-creds]&pcode=[dev-creds]"
$login = Invoke-WebRequest -Uri 'http://glidingops.test/checklogin.php' -Method POST -Body $body -ContentType "application/x-www-form-urlencoded" -UseBasicParsing -SessionVariable ws -MaximumRedirection 10

# Test API with query parameter
$api = Invoke-WebRequest -Uri 'http://glidingops.test/api/members-email.php?search=bru' -WebSession $ws -UseBasicParsing
$api.Content
```

### Important Notes

1. **Route Order in .htaccess**: More specific routes must come BEFORE less specific ones. For example:
   - `MyFlightsCSV` must come BEFORE `MyFlights` (otherwise `/MyFlightsCSV` matches `/MyFlights` first)

2. **Session Variable**: Use `-SessionVariable` on first request, then `-WebSession` on subsequent requests to maintain the session cookie.

3. **ContentType**: Set `ContentType = "application/x-www-form-urlencoded"` for POST requests to form data.

4. **UseBasicParsing**: Add this flag to avoid HTML parsing issues.

### Testing CSV Downloads

When testing CSV exports, check the headers:
```powershell
$csv.Headers['Content-Disposition']  # Should contain "attachment; filename="
$csv.Headers['Content-Type']          # Should be "text/csv"
```

### URLs

- Dev environment: `http://glidingops.test` (NOT https - SSL issues with self-signed certs)
- Test user: `[dev-creds]` / `[dev-creds]`

---

## API Development Rules

### Standard Pattern

```php
<?php
require_once __DIR__ . '/../helpers/api-base.php';

apiMaybeResumeSession();
apiRequireAuth();

// ... rest of API
```

`apiMaybeResumeSession()` - resumes session from `X-Session-Id` header if present, and sets `memberid`/`org` from headers if provided
`apiRequireAuth()` - fails with 400 if not logged in

### Other helpers

```php
// Normal exit - flushes buffers, optionally close DB connection
apiExit($con);

// Error exit - sets 400 status, returns JSON error
apiExitWithError('Error message', $con);
```

### Database config path

```php
$con_params = require(__DIR__ . '/../config/database.php');
```

NOT `./config/database.php` - the `./` path is relative to the script location when accessed via web server, which may not be correct.

---

## API Header Auth for JavaScript Fetch

APIs support header-based auth for AJAX calls (alternative to cookie-based session).

### PHP side

```php
apiMaybeResumeSession();  // resumes session from X-Session-Id header, sets memberid/org from headers
apiRequireAuth();         // fails if not logged in
```

### JavaScript side

```javascript
function getSessionId() {
    const match = document.cookie.match(/PHPSESSID=([^;]+)/);
    return match ? match[1] : '';
}

fetch('/api/some-endpoint?search=term', {
    headers: {
        'X-Session-Id': getSessionId(),
        'X-Member-Id': '<?php echo intval($_SESSION['memberid']); ?>',
        'X-Org': '<?php echo intval($_SESSION['org']); ?>'
    }
});
```

### Testing with PowerShell (for reference)

```powershell
# The browser-based approach is easiest - just open http://glidingops.test in a logged-in browser
# For curl-based testing from vagrant:
vagrant ssh -c "curl -s -H 'X-Session-Id: <session>' -H 'X-Member-Id: [member-id]' -H 'X-Org: 1' 'http://glidingops.test/api/members-email?search=smi'"