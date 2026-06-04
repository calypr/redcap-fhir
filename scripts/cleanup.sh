#!/usr/bin/env bash
set -euo pipefail

COMPOSE_CMD="docker-compose"
if ! command -v docker-compose >/dev/null 2>&1; then
  COMPOSE_CMD="docker compose"
fi

echo "Stopping services and removing orphaned resources..."
${COMPOSE_CMD} down --remove-orphans

echo "Pruning dangling Docker resources (safe)..."
docker container prune -f >/dev/null 2>&1 || true
docker network prune -f >/dev/null 2>&1 || true

echo "Cleanup complete."
