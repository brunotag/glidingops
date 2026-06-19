#!/usr/bin/env pwsh
# Launched by tools/start-dev.ps1 — starts the DOMShell MCP server with the generated token.

param(
    [string]$TokenFile = (Join-Path $PSScriptRoot ".domshell_token")
)

# Read the token
$token = if (Test-Path $TokenFile) { Get-Content $TokenFile -Raw | ForEach-Object { $_.Trim() } } else { "unknown" }

# Kill any previous DOMShell MCP server on this machine (PS5.1 compatible)
Get-CimInstance Win32_Process -Filter "Name like 'node%'" -ErrorAction SilentlyContinue | Where-Object {
    $_.CommandLine -match "@apireno/domshell"
} | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }

# Wait for ports to free up
Start-Sleep -Seconds 1

Write-Host ""
Write-Host "=== DOMShell MCP Server ===" -ForegroundColor Cyan
Write-Host "Token: " -NoNewline; Write-Host $token -ForegroundColor Yellow
Write-Host "Port:  " -NoNewline; Write-Host "3001 (HTTP MCP)" -ForegroundColor Green
Write-Host "============================" -ForegroundColor Cyan
Write-Host ""

# Start the DOMShell MCP server
npx -y @apireno/domshell --allow-write --token $token
