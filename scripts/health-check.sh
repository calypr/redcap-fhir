#!/usr/bin/env bash
set -euo pipefail

API_URL="${API_URL:-http://localhost:5000/health}"
TIMEOUT_SECONDS="${TIMEOUT_SECONDS:-20}"

if command -v curl >/dev/null 2>&1; then
  RESPONSE="$(curl -fsS --max-time "${TIMEOUT_SECONDS}" "${API_URL}")"
else
  echo "curl is required for health checks" >&2
  exit 1
fi

if echo "${RESPONSE}" | grep -q '"status"[[:space:]]*:[[:space:]]*"healthy"'; then
  echo "FHIR API health check passed: ${API_URL}"
  exit 0
fi

echo "FHIR API health check failed: ${API_URL}" >&2
echo "Response: ${RESPONSE}" >&2
exit 1
