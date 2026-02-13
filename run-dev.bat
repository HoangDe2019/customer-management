@echo off
setlocal
set PHP_BIN=php
set LARAVEL_ROOT=%~dp0
set FRONTEND_DIR=%~dp0frontend
set LARAVEL_HOST=0.0.0.0
set LARAVEL_PORT=8000

cd /d "%LARAVEL_ROOT%"

REM Start queue worker in new CMD window
start "Laravel Queue Worker" cmd /k "cd /d "%LARAVEL_ROOT%" && %PHP_BIN% artisan queue:work"

REM Start Reverb in new CMD window
start "Laravel Reverb" cmd /k "cd /d "%LARAVEL_ROOT%" && %PHP_BIN% artisan reverb:start"

REM Start Laravel HTTP server in new CMD window
start "Laravel Server" cmd /k "cd /d "%LARAVEL_ROOT%" && %PHP_BIN% artisan serve --host=%LARAVEL_HOST% --port=%LARAVEL_PORT%"

REM Start Vite in new CMD window
if exist "%FRONTEND_DIR%" (
  start "Vite Dev Server" cmd /k "cd /d "%FRONTEND_DIR%" && npm run dev -- --host"
) else (
  echo [WARN] Frontend folder not found at "%FRONTEND_DIR%".
  pause
)

echo All services started in separate windows.
echo Close each window individually to stop its service.
endlocal