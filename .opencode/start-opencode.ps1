#!/usr/bin/env pwsh
# Launched by tools/start-dev.ps1 — sets DOMSHELL_TOKEN and starts opencode.

$tf = Join-Path $PSScriptRoot ".domshell_token"
$t = if (Test-Path $tf) { Get-Content $tf -Raw | ForEach-Object { $_.Trim() } } else { "unknown" }
$env:DOMSHELL_TOKEN = $t

Write-Host "DOMSHELL_TOKEN set, starting opencode..." -ForegroundColor Gray
opencode
