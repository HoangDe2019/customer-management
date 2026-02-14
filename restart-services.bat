@echo off
setlocal

echo ============================================
echo Restarting Laravel Development Services
echo ============================================
echo.

REM Stop all services first
call stop-services.bat

echo.
echo Waiting 3 seconds before restart...
timeout /t 3 /nobreak >nul
echo.

REM Start all services
call start-services.bat

endlocal
