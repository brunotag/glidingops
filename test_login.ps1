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

# Step 4: Visit MyFlights to establish session properly
$myflights = Invoke-WebRequest -Uri 'http://192.168.10.10/MyFlights' -WebSession $webSession -UseBasicParsing
Write-Host "MyFlights:" $myflights.StatusCode

# Step 5: Test the MyFlights API - direct file
$api = Invoke-WebRequest -Uri 'http://192.168.10.10/api/myflights-data.php' -WebSession $webSession -UseBasicParsing
Write-Host "API Status:" $api.StatusCode
Write-Host "API Content-Type:" $api.Headers['Content-Type']
Write-Host "API Content:" $api.Content.Substring(0, [Math]::Min(500, $api.Content.Length))
Write-Host "CSV:" $csv.StatusCode "Length:" $csv.Content.Length
Write-Host "CSV Content:" $csv.Content.Substring(0, [Math]::Min(300, $csv.Content.Length))