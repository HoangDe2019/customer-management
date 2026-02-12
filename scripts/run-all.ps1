# One script: deploy (install, migrate, build) then run all services (API, Queue, Reverb, Frontend).
# Usage: .\scripts\run-all.ps1
# Stop with Ctrl+C.
$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
Set-Location $ProjectRoot

Write-Host "=== Run All: Deploy + Start Services ===" -ForegroundColor Cyan
& "$ScriptDir\deploy.ps1"
if (-not $?) { exit 1 }

Write-Host "`n=== Starting API, Queue, Reverb, Frontend ===" -ForegroundColor Cyan
Write-Host "API: http://localhost:8000  Frontend: http://localhost:5173  Reverb: ws://localhost:8080" -ForegroundColor Gray
& "$ScriptDir\run.ps1"
if (-not $?) { exit 1 }
