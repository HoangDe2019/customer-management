# Deploy only: install deps, migrate, build frontend. No servers started.
# Usage: .\scripts\deploy.ps1
$ErrorActionPreference = "Stop"
$ProjectRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $ProjectRoot

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    php artisan key:generate
}

Write-Host "Composer install..." -ForegroundColor Cyan
composer install --no-interaction
Write-Host "Migrations..." -ForegroundColor Cyan
php artisan migrate --force
Write-Host "Frontend install + build..." -ForegroundColor Cyan
Set-Location frontend
npm ci 2>$null; if (-not $?) { npm install }
npm run build
Set-Location $ProjectRoot
Write-Host "Deploy complete. Run .\scripts\run.ps1 to start API + Queue + Frontend." -ForegroundColor Green
