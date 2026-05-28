$AGENTMEMORY_DIR = Join-Path $PSScriptRoot ".agentmemory"
$PROJ = $PWD.Path
$LARAVEL = Join-Path $PSScriptRoot "lrv"
$TOKEN_FILE = Join-Path $PSScriptRoot ".opencode\.domshell_token"

# Generate random DOMSHELL_TOKEN for browser automation (32 hex chars)
$token = -join (1..32 | ForEach-Object { '0123456789abcdef'[(Get-Random -Maximum 16)] })
$env:DOMSHELL_TOKEN = $token
$token | Out-File -Encoding utf8 -FilePath $TOKEN_FILE

# Open all service tabs in a single wt command (focuses once instead of 4 times)
wt -w 0 nt -d $AGENTMEMORY_DIR --title "iii-engine" --tabColor "#44a39b" powershell -NoExit -Command "./run-cmd.cmd" `; `
    nt -d $AGENTMEMORY_DIR --title "agentmemory" --tabColor "#f3cd21" powershell -NoExit -Command "npx -y @agentmemory/agentmemory" `; `
    nt -d $PROJ --title "domshell" --tabColor "#e06c75" powershell -NoExit -File "$PROJ\.opencode\start-domshell.ps1" `; `
    nt -d $LARAVEL --title "vagrant" --tabColor "#201390" powershell -NoExit -Command "vagrant up"
Start-Sleep 1
wt -w 0 focus-tab --target 0 2>$null

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  DOMShell Browser Automation Setup           " -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Token: " -NoNewline; Write-Host $token -ForegroundColor White
Write-Host ""
Write-Host "  opencode will connect automatically with the token above." -ForegroundColor Green
Write-Host ""
Write-Host "  If you haven't already:" -ForegroundColor Yellow
Write-Host "  1. Open Chrome" -ForegroundColor Gray
Write-Host "  2. Right-click DOMShell icon -> Options" -ForegroundColor Gray
Write-Host "  3. Check the 'MCP Bridge' URL is: " -NoNewline; Write-Host "ws://localhost:9876" -ForegroundColor Blue
Write-Host "  4. If there's a token field, paste the value above" -ForegroundColor Gray
Write-Host "  5. Click Save" -ForegroundColor Gray
Write-Host ""
Write-Host "Press Enter to start opencode..." -ForegroundColor Green
$null = Read-Host
Write-Host "Starting opencode..." -ForegroundColor Green
Write-Host ""

wt -w 0 nt -d $PROJ --title "opencode" --tabColor "#90137b" powershell -NoExit -File "$PROJ\.opencode\start-opencode.ps1"