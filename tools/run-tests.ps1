<#
.SYNOPSIS
    Run PHPUnit test suite.
.DESCRIPTION
    Default: runs integration tests via vagrant ssh (requires Vagrant box).
    -Unit:   runs unit tests only (pure PHP, no Vagrant needed).
.PARAMETER Filter
    Optional --filter argument, e.g. "NavigationTest" or "NavigationTest::testHomeLoads".
.PARAMETER Unit
    Run unit tests only (no Vagrant/DB needed).
.PARAMETER Help
    Show this help.
.EXAMPLE
    .\tools\run-tests
    .\tools\run-tests -Unit
    .\tools\run-tests -Filter NavigationTest
    .\tools\run-tests -Unit -Filter BillingReportTest
#>

param(
    [string]$Filter = "",
    [switch]$Unit,
    [switch]$Help
)

if ($Help) {
    Get-Help $PSCommandPath
    exit 0
}

$PROJ = (Get-Item $PSScriptRoot\..).FullName
$LARAVEL = Join-Path $PROJ "lrv"

$cfg = if ($Unit) { "phpunit.unit.xml" } else { "phpunit.xml" }
$label = if ($Unit) { "Unit Tests" } else { "Integration Tests" }

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  PHPUnit - $label" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
if ($Filter) {
    Write-Host "Filter: " -NoNewline -ForegroundColor Yellow
    Write-Host $Filter -ForegroundColor White
}
Write-Host ""

$cmd = "cd /home/vagrant/code && ./lrv/vendor/bin/phpunit -c $cfg"
if ($Filter) {
    $cmd += " --filter=$Filter"
}

Push-Location $LARAVEL
$output = vagrant ssh -c "$cmd" 2>&1
$exitCode = $LASTEXITCODE
Pop-Location

Write-Host $output

Write-Host ""
if ($exitCode -eq 0) {
    Write-Host "All tests passed!" -ForegroundColor Green
} else {
    Write-Host "Some tests failed (exit code: $exitCode)" -ForegroundColor Red
}

exit $exitCode
