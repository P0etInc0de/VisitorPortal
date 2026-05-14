@echo off
docker compose --env-file .env.demo -f docker-compose.demo.yml pull
if errorlevel 1 exit /b 1
docker compose --env-file .env.demo -f docker-compose.demo.yml up -d
if errorlevel 1 exit /b 1
echo Besucherportal updated. Open http://localhost:8080
