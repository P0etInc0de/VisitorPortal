#!/usr/bin/env sh
set -eu

COMPOSE_FILE="docker-compose.demo.yml"
ENV_FILE=".env.demo"
ENV_EXAMPLE=".env.demo.example"
APP_URL="http://localhost:8080"

if [ ! -f "$ENV_FILE" ]; then
    if [ ! -f "$ENV_EXAMPLE" ]; then
        echo "Missing $ENV_EXAMPLE. Cannot create local demo environment file."
        exit 1
    fi

    cp "$ENV_EXAMPLE" "$ENV_FILE"
    echo "Created $ENV_FILE from $ENV_EXAMPLE."
fi

if ! docker info >/dev/null 2>&1; then
    echo "Docker is not running. Please start Docker Desktop and run this script again."
    exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
    echo "Docker Compose v2 is required."
    echo "Please update Docker Desktop or install the Docker Compose plugin."
    exit 1
fi

echo "[1/3] Pulling Besucherportal images..."
docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" pull || echo "Image pull failed. Continuing with locally available images if present."

echo "[2/3] Starting Besucherportal..."
docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" up -d

echo "[3/3] Waiting for the portal to become reachable..."
i=1
while [ "$i" -le 60 ]; do
    if command -v curl >/dev/null 2>&1 && curl -fsS "$APP_URL" >/dev/null 2>&1; then
        break
    fi

    if [ "$i" -eq 60 ]; then
        echo "The containers started, but the app did not answer in time."
        echo "Run: docker compose --env-file $ENV_FILE -f $COMPOSE_FILE logs --tail=120 app"
        exit 1
    fi

    sleep 2
    i=$((i + 1))
done

cat <<'EOF'

+------------------------------------------------------------+
| SUCCESS! Besucherportal is running.                         |
|                                                            |
| App:      http://localhost:8080                            |
| Mailhog:  http://localhost:8025                            |
|                                                            |
| Login:    admin@example.org                                |
| Password: ChangeMe-42!                                     |
+------------------------------------------------------------+

EOF
