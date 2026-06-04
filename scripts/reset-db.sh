#!/usr/bin/env bash
set -euo pipefail

COMPOSE_CMD="docker-compose"
if ! command -v docker-compose >/dev/null 2>&1; then
  COMPOSE_CMD="docker compose"
fi

echo "Stopping services and removing volumes..."
${COMPOSE_CMD} down -v --remove-orphans

echo "Starting core services..."
${COMPOSE_CMD} up -d mysql fhir-api

echo "Re-initializing schema..."
./scripts/init.sh

echo "Database reset complete."
