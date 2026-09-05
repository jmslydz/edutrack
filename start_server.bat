@echo off
echo ============================================
echo   EduTrack - Starting Development Server
echo   URL: http://localhost:3000
echo   Close this window to stop the server
echo ============================================
echo.
cd /d "%~dp0"
php -S 0.0.0.0:3000 index.php
pause
