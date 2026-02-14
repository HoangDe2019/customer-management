@echo off
setlocal

set PHP_BIN=php
set LARAVEL_ROOT=%~dp0

cd /d "%LARAVEL_ROOT%"

echo ============================================
echo Laravel Services Status Check
echo ============================================
echo.

REM Check Laravel Server
tasklist /FI "WINDOWTITLE eq Laravel Server*" 2>nul | find /i "cmd.exe" >nul
if errorlevel 1 (
    echo [X] Laravel Server:     NOT RUNNING
) else (
    echo [√] Laravel Server:     RUNNING
)

REM Check Reverb
tasklist /FI "WINDOWTITLE eq Laravel Reverb*" 2>nul | find /i "cmd.exe" >nul
if errorlevel 1 (
    echo [X] Reverb WebSocket:   NOT RUNNING
) else (
    echo [√] Reverb WebSocket:   RUNNING
)

REM Check Queue Worker
tasklist /FI "WINDOWTITLE eq Laravel Queue Worker*" 2>nul | find /i "cmd.exe" >nul
if errorlevel 1 (
    echo [X] Queue Worker:       NOT RUNNING
) else (
    echo [√] Queue Worker:       RUNNING
)

REM Check Vite
tasklist /FI "WINDOWTITLE eq Vite Dev Server*" 2>nul | find /i "cmd.exe" >nul
if errorlevel 1 (
    echo [X] Vite Dev Server:    NOT RUNNING
) else (
    echo [√] Vite Dev Server:    RUNNING
)

echo.
echo ============================================
echo.
pause

endlocal
