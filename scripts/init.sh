#!/usr/bin/env bash
set -euo pipefail

COMPOSE_CMD="docker-compose"
if ! command -v docker-compose >/dev/null 2>&1; then
  COMPOSE_CMD="docker compose"
fi

echo "Initializing REDCap database schema..."
${COMPOSE_CMD} exec -T mysql mysql \
  -u root -p"${MYSQL_ROOT_PASSWORD:-redcap_root_pass}" \
  "${MYSQL_DATABASE:-redcap}" < scripts/init.sql

echo "Database initialization complete."
