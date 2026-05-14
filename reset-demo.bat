@echo off
setlocal

echo This deletes the local demo database and uploaded files.
set /p ANSWER=Continue? [y/N] 

if /I "%ANSWER%"=="y" goto reset
if /I "%ANSWER%"=="yes" goto reset
echo Reset cancelled.
exit /b 0

:reset
docker compose --env-file .env.demo -f docker-compose.demo.yml down -v
call "%~dp0start.bat"

endlocal
