# Contributing

Thanks for contributing to REDCap FHIR Integration.

## Development setup

1. Clone the repository.
2. Copy environment settings:
   ```bash
   cp .env.example .env
   ```
3. Start local services:
   ```bash
   docker-compose up -d
   ```
4. Verify the API is healthy:
   ```bash
   ./scripts/health-check.sh
   ```

## Branch and PR workflow

1. Create a feature branch from `main`.
2. Keep changes focused and small.
3. Run local checks before opening a PR.
4. Open a pull request with:
   - clear summary
   - test/verification notes
   - rollback notes for operational changes

## Testing and validation

This repository currently has no automated unit test suite.
Use these checks before submitting:

```bash
bash -n scripts/*.sh
python -m compileall -q src
./scripts/health-check.sh
```

For integration checks, run CRUD calls documented in `API.md`.

## Deployment guidelines

1. Review `.env` values for the target environment.
2. Build and start services:
   ```bash
   docker-compose up -d --build
   ```
3. Validate service health:
   ```bash
   ./scripts/health-check.sh
   ```
4. Monitor startup logs:
   ```bash
   ./scripts/collect-logs.sh
   ```

## Security and operations

- Do not commit secrets in `.env`.
- Use strong MySQL passwords in non-local environments.
- Keep supported FHIR resource types aligned with `src/config.py`.

## Troubleshooting

See `TROUBLESHOOTING.md` for common failure scenarios and fixes.
