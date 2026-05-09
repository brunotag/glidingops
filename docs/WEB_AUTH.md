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

- Dev environment: `http://192.168.10.10` or `http://glidingops.test`
- Test user: `[dev-creds]` / `[dev-creds]`