@echo off
setlocal

echo ============================================
echo Stopping Laravel Development Services
echo ============================================
echo.

REM Kill Laravel Server
echo [1/4] Stopping Laravel Server...
taskkill /FI "WINDOWTITLE eq Laravel Server*" /T /F >nul 2>&1
if errorlevel 1 (
    echo       Not running or already stopped
) else (
    echo       Stopped
)

REM Kill Reverb
echo [2/4] Stopping Reverb WebSocket Server...
taskkill /FI "WINDOWTITLE eq Laravel Reverb*" /T /F >nul 2>&1
if errorlevel 1 (
    echo       Not running or already stopped
) else (
    echo       Stopped
)

REM Kill Queue Worker
echo [3/4] Stopping Queue Worker...
taskkill /FI "WINDOWTITLE eq Laravel Queue Worker*" /T /F >nul 2>&1
if errorlevel 1 (
    echo       Not running or already stopped
) else (
    echo       Stopped
)

REM Kill Vite
echo [4/4] Stopping Vite Dev Server...
taskkill /FI "WINDOWTITLE eq Vite Dev Server*" /T /F >nul 2>&1
if errorlevel 1 (
    echo       Not running or already stopped
) else (
    echo       Stopped
)

echo.
echo ============================================
echo All Services Stopped
echo ============================================
echo.
pause

endlocal
