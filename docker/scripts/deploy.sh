#!/usr/bin/env sh
set -eu

ROOT_DIR="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

ENV_FILE="${DOCKER_ENV_FILE:-.env}"

if [ ! -f "$ENV_FILE" ]; then
    echo "Missing $ENV_FILE. Copy .env.docker.example and set secrets first."
    echo "  cp .env.docker.example .env"
    echo "  php docker/scripts/prepare-env-docker.php   # optional: generate secrets"
    exit 1
fi

export DOCKER_ENV_FILE="$ENV_FILE"

docker compose --env-file "$ENV_FILE" build
docker compose --env-file "$ENV_FILE" up -d
docker compose --env-file "$ENV_FILE" ps

echo ""
echo "Application URL: $(grep '^APP_URL=' "$ENV_FILE" | cut -d= -f2-)"
echo "Health check:    $(grep '^APP_URL=' "$ENV_FILE" | cut -d= -f2-)/up"
