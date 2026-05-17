@echo off
set ENV_FILE=.env.demo
set ENV_EXAMPLE=.env.demo.example
if not exist "%ENV_FILE%" if exist "%ENV_EXAMPLE%" copy "%ENV_EXAMPLE%" "%ENV_FILE%" >nul
docker compose --env-file "%ENV_FILE%" -f docker-compose.demo.yml down
echo Besucherportal stopped. Data is still stored in Docker volumes.
