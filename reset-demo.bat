@echo off
setlocal

echo This deletes the local demo database and uploaded files.
set /p ANSWER=Continue? [y/N] 

set ENV_FILE=.env.demo
set ENV_EXAMPLE=.env.demo.example
if not exist "%ENV_FILE%" if exist "%ENV_EXAMPLE%" copy "%ENV_EXAMPLE%" "%ENV_FILE%" >nul

if /I "%ANSWER%"=="y" goto reset
if /I "%ANSWER%"=="yes" goto reset
echo Reset cancelled.
exit /b 0

:reset
docker compose --env-file "%ENV_FILE%" -f docker-compose.demo.yml down -v
call "%~dp0start.bat"

endlocal
