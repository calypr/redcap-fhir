#!/usr/bin/env bash
set -euo pipefail

COMPOSE_CMD="docker-compose"
if ! command -v docker-compose >/dev/null 2>&1; then
  COMPOSE_CMD="docker compose"
fi

OUT_DIR="${1:-./logs}"
mkdir -p "${OUT_DIR}"
STAMP="$(date +%Y%m%d-%H%M%S)"
OUT_FILE="${OUT_DIR}/compose-logs-${STAMP}.log"

echo "Collecting service logs into ${OUT_FILE} ..."
${COMPOSE_CMD} logs --no-color > "${OUT_FILE}"

echo "Done."
