@echo off
docker compose --env-file .env.demo -f docker-compose.demo.yml down
echo Besucherportal stopped. Data is still stored in Docker volumes.
