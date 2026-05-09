# Login and test API
$login = Invoke-WebRequest -Uri 'http://glidingops.test/Login.php' -UseBasicParsing -SessionVariable 'webSession'

$body = "user=fgordon&pcode=fgordon"
$loginResult = Invoke-WebRequest -Uri 'http://glidingops.test/checklogin.php' -WebSession $webSession -Method POST -Body $body -ContentType "application/x-www-form-urlencoded" -UseBasicParsing -MaximumRedirection 10
Write-Host "Login:" $loginResult.StatusCode

# Visit MyFlights to establish session
$myflights = Invoke-WebRequest -Uri 'http://glidingops.test/MyFlights' -WebSession $webSession -UseBasicParsing
Write-Host "MyFlights page:" $myflights.StatusCode

# Check cookies
$cookies = $webSession.Cookies.GetCookies("http://glidingops.test/")
Write-Host "Cookies after MyFlights:"
$cookies | ForEach-Object { Write-Host "  $($_.Name) = $($_.Value)" }

# Try adding session cookie manually to the request
$cookieHeader = "PHPSESSID=" + ($cookies | Where-Object { $_.Name -eq "PHPSESSID" }).Value
Write-Host "Cookie header: $cookieHeader"

# Now test the API - with manual cookie
try {
    $api = Invoke-WebRequest -Uri 'http://glidingops.test/api/myflights-data.php' -WebSession $webSession -UseBasicParsing -Headers @{"Cookie"=$cookieHeader}
    Write-Host "API Status:" $api.StatusCode
    Write-Host "Content:" $api.Content.Substring(0, [Math]::Min(500, $api.Content.Length))
} catch {
    Write-Host "Error:" $_.Exception.Message
}
Write-Host "API Status:" $api.StatusCode
Write-Host "Content-Type:" $api.Headers['Content-Type']
Write-Host "Length:" $api.Content.Length
Write-Host "First 500 chars:"
Write-Host $api.Content.Substring(0, [Math]::Min(500, $api.Content.Length))