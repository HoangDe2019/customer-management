@echo off
setlocal enabledelayedexpansion

REM ============================================
REM Laravel Development Services Launcher
REM ============================================

set PHP_BIN=php
set LARAVEL_ROOT=%~dp0
set FRONTEND_DIR=%~dp0frontend
set LARAVEL_HOST=0.0.0.0
set LARAVEL_PORT=8000
set REVERB_PORT=8080

echo ============================================
echo Starting Laravel Development Environment
echo ============================================
echo.

cd /d "%LARAVEL_ROOT%"

REM Check if PHP is available
%PHP_BIN% -v >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP not found in PATH!
    echo Please install PHP or add it to your system PATH.
    pause
    exit /b 1
)

REM Check if composer dependencies are installed
if not exist "vendor" (
    echo [ERROR] Vendor folder not found!
    echo Please run: composer install
    pause
    exit /b 1
)

REM Clear caches before starting
echo [1/5] Clearing configuration cache...
%PHP_BIN% artisan config:clear >nul 2>&1
%PHP_BIN% artisan cache:clear >nul 2>&1
%PHP_BIN% artisan view:clear >nul 2>&1
%PHP_BIN% artisan route:clear >nul 2>&1
echo       Done!
echo.

REM Start Queue Worker
echo [2/5] Starting Queue Worker...
start "Laravel Queue Worker" cmd /k "title Laravel Queue Worker && cd /d "%LARAVEL_ROOT%" && echo Queue Worker Starting... && %PHP_BIN% artisan queue:work --tries=3 --timeout=90"
timeout /t 2 /nobreak >nul
echo       Queue Worker started in new window
echo.

REM Start Reverb WebSocket Server
echo [3/5] Starting Reverb WebSocket Server...
start "Laravel Reverb" cmd /k "title Laravel Reverb && cd /d "%LARAVEL_ROOT%" && echo Reverb Starting on port %REVERB_PORT%... && %PHP_BIN% artisan reverb:start"
timeout /t 3 /nobreak >nul
echo       Reverb started on port %REVERB_PORT%
echo.

REM Start Laravel HTTP Server
echo [4/5] Starting Laravel HTTP Server...
start "Laravel Server" cmd /k "title Laravel Server && cd /d "%LARAVEL_ROOT%" && echo Laravel Starting on http://%LARAVEL_HOST%:%LARAVEL_PORT%... && %PHP_BIN% artisan serve --host=%LARAVEL_HOST% --port=%LARAVEL_PORT%"
timeout /t 2 /nobreak >nul
echo       Laravel Server started on http://localhost:%LARAVEL_PORT%
echo.

REM Start Vite Dev Server
if exist "%FRONTEND_DIR%" (
    echo [5/5] Starting Vite Dev Server...
    start "Vite Dev Server" cmd /k "title Vite Dev Server && cd /d "%FRONTEND_DIR%" && echo Vite Starting... && npm run dev -- --host"
    timeout /t 2 /nobreak >nul
    echo       Vite started
) else (
    echo [5/5] Skipping Vite (frontend folder not found)
)

echo.
echo ============================================
echo All Services Started Successfully!
echo ============================================
echo.
echo Services Running:
echo   - Laravel Server:    http://localhost:%LARAVEL_PORT%
echo   - Reverb WebSocket:  ws://localhost:%REVERB_PORT%
echo   - Queue Worker:      Running
if exist "%FRONTEND_DIR%" (
    echo   - Vite Dev Server:   Running
)
echo.
echo To stop all services, run: stop-services.bat
echo Or close each window individually.
echo.
echo Press any key to exit this launcher...
pause >nul

endlocal
