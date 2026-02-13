@echo off
setlocal
set PHP_BIN=php
set LARAVEL_ROOT=%~dp0
set FRONTEND_DIR=%~dp0frontend
set LARAVEL_HOST=0.0.0.0
set LARAVEL_PORT=8002

cd /d "%LARAVEL_ROOT%"

REM Start queue worker in background
start /b "" %PHP_BIN% artisan queue:work

REM Start Reverb in background
start /b "" %PHP_BIN% artisan reverb:start

REM Start Laravel HTTP server in background
start /b "" %PHP_BIN% artisan serve --host=%LARAVEL_HOST% --port=%LARAVEL_PORT%

REM Start Vite in foreground (so you can Ctrl+C to stop everything after)
if exist "%FRONTEND_DIR%" (
  pushd "%FRONTEND_DIR%"
  npm run dev -- --host
  popd
) else (
  echo [WARN] Frontend folder not found at "%FRONTEND_DIR%".
)

echo Press Ctrl+C to stop foreground process. Background processes may keep running.
pause
endlocal
