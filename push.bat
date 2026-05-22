@echo off
REM ============================================================
REM  MediaServer - Push to GitHub
REM  Run this from Windows to push all changes to GitHub
REM  Usage: push.bat "commit message"
REM ============================================================

cd /d "%~dp0"

set MSG=%~1
if "%MSG%"=="" set MSG=update

echo.
echo [MediaServer] Pushing to GitHub...
echo.

git add -A
git commit -m "%MSG%" 2>nul || echo (nothing new to commit)
git push origin main 2>nul || git push origin master

echo.
echo [MediaServer] Done. Now run on your server:
echo.
echo   ssh root@YOUR_SERVER_IP
echo   bash ^<(curl -fsSL https://raw.githubusercontent.com/basilkewir/media-server1/main/server-install.sh)
echo.
pause
