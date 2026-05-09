# Web Authentication Testing

## How to Authenticate and Test Pages with Session

Use PowerShell's WebRequestSession to maintain cookies across requests.

### PowerShell Script Template

```powershell
# Step 1: Get login page and capture session
$login = Invoke-WebRequest -Uri 'http://192.168.10.10/Login.php' -UseBasicParsing -SessionVariable 'webSession'

# Step 2: Submit login form (username: [dev-creds], password: [dev-creds])
$body = "user=[dev-creds]&pcode=[dev-creds]"
$loginResult = Invoke-WebRequest -Uri 'http://192.168.10.10/checklogin.php' -WebSession $webSession -Method POST -Body $body -ContentType "application/x-www-form-urlencoded" -UseBasicParsing -MaximumRedirection 10

# Step 3: Visit home to ensure session is established
$homeResult = Invoke-WebRequest -Uri 'http://192.168.10.10/home' -WebSession $webSession -UseBasicParsing

# Step 4: Now you can access authenticated pages
$page = Invoke-WebRequest -Uri 'http://192.168.10.10/TargetPage' -WebSession $webSession -UseBasicParsing
Write-Host $page.Content
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

### 1. Always call `session_start()` before any output

```php
<?php
session_start();  // Must be first!
require_once __DIR__ . '/../helpers/api-base.php';
```

### 2. Use `apiExit($con)` to terminate, NOT `apiExitWithError()`

The `apiExitWithError()` function does NOT exist - it was never defined. Use this pattern instead:

```php
// For errors:
http_response_code(403);
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Security level too low']);
apiExit($con);

// For DB connection errors:
http_response_code(500);
header('Content-Type: application/json');
echo json_encode(['error' => 'Database connection failed']);
apiExit($con);
```

### 3. Database config path

```php
$con_params = require(__DIR__ . '/../config/database.php');
```

NOT `./config/database.php` - the `./` path is relative to the script location when accessed via web server, which may not be correct.

### 4. Test API with authenticated session first

Use PowerShell with session variable to test authenticated APIs:

```powershell
# Login
$body = "user=[dev-creds]&pcode=[dev-creds]"
$login = Invoke-WebRequest -Uri 'https://glidingops.test/checklogin.php' -Method POST -Body $body -ContentType "application/x-www-form-urlencoded" -UseBasicParsing -SessionVariable ws -MaximumRedirection 10

# Test API
$result = Invoke-WebRequest -Uri 'https://glidingops.test/api/texts' -WebSession $ws -UseBasicParsing
$result.Content | ConvertFrom-Json
```