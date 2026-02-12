# Run all services: Laravel API + Queue + Frontend dev
# Usage: .\scripts\run.ps1   or   .\scripts\run.ps1 -DeployFirst
param(
    [switch]$DeployFirst  # Run composer install, npm install, migrate, frontend build before starting
)

$ErrorActionPreference = "Stop"
$ProjectRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $ProjectRoot

function EnsureEnv {
    if (-not (Test-Path ".env")) {
        Write-Host "Creating .env from .env.example..." -ForegroundColor Yellow
        Copy-Item ".env.example" ".env"
        php artisan key:generate
    }
}

function Deploy {
    Write-Host "`n=== Deploy (install + migrate + build) ===" -ForegroundColor Cyan
    EnsureEnv
    composer install --no-interaction
    php artisan migrate --force 2>$null
    if (-not $?) { php artisan migrate --force }
    Set-Location frontend
    npm ci 2>$null; if (-not $?) { npm install }
    npm run build
    Set-Location $ProjectRoot
    Write-Host "Deploy done.`n" -ForegroundColor Green
}

if ($DeployFirst) {
    Deploy
}

# Check .env exists for run
EnsureEnv

Write-Host "Starting: Laravel (API), Queue worker, Reverb (WebSocket), Frontend (Vite)..." -ForegroundColor Cyan
Write-Host "Stop with Ctrl+C. API: http://localhost:8000  Frontend: http://localhost:5173  Reverb: ws://localhost:8080`n" -ForegroundColor Gray

# Run API, queue, reverb, and frontend (reverb only if BROADCAST_CONNECTION=reverb in .env)
npx concurrently -k -n "api,queue,reverb,frontend" -c "blue,magenta,yellow,green" `
    "php artisan serve" `
    "php artisan queue:work --tries=3" `
    "php artisan reverb:start" `
    "npm run dev --prefix frontend"
