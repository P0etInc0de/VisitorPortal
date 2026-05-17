#!/usr/bin/env sh
set -eu

echo "This deletes the local demo database and uploaded files."
printf "Continue? [y/N] "
read answer

ENV_FILE=".env.demo"
ENV_EXAMPLE=".env.demo.example"

if [ ! -f "$ENV_FILE" ] && [ -f "$ENV_EXAMPLE" ]; then
    cp "$ENV_EXAMPLE" "$ENV_FILE"
fi

case "$answer" in
    y|Y|yes|YES)
        docker compose --env-file "$ENV_FILE" -f docker-compose.demo.yml down -v
        ./start.sh
        ;;
    *)
        echo "Reset cancelled."
        ;;
esac
