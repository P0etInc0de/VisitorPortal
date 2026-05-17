#!/usr/bin/env sh
set -eu

ENV_FILE=".env.demo"
ENV_EXAMPLE=".env.demo.example"

if [ ! -f "$ENV_FILE" ] && [ -f "$ENV_EXAMPLE" ]; then
    cp "$ENV_EXAMPLE" "$ENV_FILE"
fi

docker compose --env-file "$ENV_FILE" -f docker-compose.demo.yml pull
docker compose --env-file "$ENV_FILE" -f docker-compose.demo.yml up -d
echo "Besucherportal updated. Open http://localhost:8080"
