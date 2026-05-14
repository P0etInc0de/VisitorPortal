#!/usr/bin/env sh
set -eu

echo "This deletes the local demo database and uploaded files."
printf "Continue? [y/N] "
read answer

case "$answer" in
    y|Y|yes|YES)
        docker compose --env-file .env.demo -f docker-compose.demo.yml down -v
        ./start.sh
        ;;
    *)
        echo "Reset cancelled."
        ;;
esac
