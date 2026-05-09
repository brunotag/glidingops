# Step 1: Get login page and capture session
$login = Invoke-WebRequest -Uri 'http://192.168.10.10/Login.php' -UseBasicParsing -SessionVariable 'webSession'
Write-Host "Got login page, session:" $webSession.GetType().Name

# Step 2: Submit login form
$body = "user=fgordon&pcode=fgordon"
$loginResult = Invoke-WebRequest -Uri 'http://192.168.10.10/checklogin.php' -WebSession $webSession -Method POST -Body $body -ContentType "application/x-www-form-urlencoded" -UseBasicParsing -MaximumRedirection 10
Write-Host "Login result:" $loginResult.StatusCode "Length:" $loginResult.Content.Length

# Check if it's login failure or success
if ($loginResult.Content -match "Wrong Username") {
    Write-Host "LOGIN FAILED!"
} else {
    Write-Host "LOGIN SUCCESS (got home page)"
}

# Step 3: Visit home to ensure session is established
$homeResult = Invoke-WebRequest -Uri 'http://192.168.10.10/home' -WebSession $webSession -UseBasicParsing
Write-Host "Home:" $homeResult.StatusCode

# Step 4: Try CSV - use direct file path with query param for test mode
$csv = Invoke-WebRequest -Uri 'http://192.168.10.10/MyFlightsCSV.php?test=1' -WebSession $webSession -UseBasicParsing

# Check headers
Write-Host "CSV Headers:"
$csv.Headers.Keys | ForEach-Object { Write-Host "  $_ : $($csv.Headers[$_])" }

Write-Host ""
Write-Host "CSV Content first 500 chars:"
Write-Host $csv.Content.Substring(0, [Math]::Min(500, $csv.Content.Length))
Write-Host "CSV:" $csv.StatusCode "Length:" $csv.Content.Length
Write-Host "CSV Content:" $csv.Content.Substring(0, [Math]::Min(300, $csv.Content.Length))