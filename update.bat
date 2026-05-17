@echo off
set ENV_FILE=.env.demo
set ENV_EXAMPLE=.env.demo.example
if not exist "%ENV_FILE%" if exist "%ENV_EXAMPLE%" copy "%ENV_EXAMPLE%" "%ENV_FILE%" >nul
docker compose --env-file "%ENV_FILE%" -f docker-compose.demo.yml pull
if errorlevel 1 exit /b 1
docker compose --env-file "%ENV_FILE%" -f docker-compose.demo.yml up -d
if errorlevel 1 exit /b 1
echo Besucherportal updated. Open http://localhost:8080
