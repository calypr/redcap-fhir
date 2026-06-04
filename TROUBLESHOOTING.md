# Troubleshooting Guide

## Service startup failures

### `docker-compose up` fails

- Validate environment file exists: `ls -la .env`
- Validate compose config: `docker-compose config`
- Rebuild images if dependencies changed:
  ```bash
  docker-compose build --no-cache
  ```

### MySQL stays unhealthy

- Check logs:
  ```bash
  docker-compose logs mysql
  ```
- Verify credentials in `.env` match compose values.
- Reset database volume (destructive):
  ```bash
  ./scripts/reset-db.sh
  ```

## API returns connection/database errors

- Check API logs:
  ```bash
  docker-compose logs fhir-api
  ```
- Confirm MySQL is reachable from API container:
  ```bash
  docker-compose exec fhir-api getent hosts mysql
  ```
- Ensure `MYSQL_*` settings are consistent in `.env`.

## FHIR request errors

### `resourceType is required`

- Include `resourceType` in request JSON body.

### `Resource validation failed`

- Verify payload matches FHIR R5 for that resource.
- Confirm `resourceType` is listed by:
  ```bash
  curl http://localhost:5000/fhir/supported-resources
  ```

### Resource type not supported

- Use one of the supported resource types from `src/config.py`.

## Health check fails

- Run:
  ```bash
  ./scripts/health-check.sh
  ```
- If API is down, start/restart services:
  ```bash
  docker-compose up -d
  ```

## Log collection and cleanup

- Aggregate current logs:
  ```bash
  ./scripts/collect-logs.sh
  ```
- Clean up stopped containers/networks:
  ```bash
  ./scripts/cleanup.sh
  ```
