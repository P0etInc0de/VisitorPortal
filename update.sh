#!/usr/bin/env sh
set -eu

docker compose --env-file .env.demo -f docker-compose.demo.yml pull
docker compose --env-file .env.demo -f docker-compose.demo.yml up -d
echo "Besucherportal updated. Open http://localhost:8080"
