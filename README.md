# REDCap FHIR Integration

A Docker-based REDCap instance configured for CRUD operations on FHIR data from [fhir-aggregator.org](https://fhir-aggregator.org/).

## Overview

This project launches a REDCap instance in a Docker container with integrated FHIR capabilities, enabling seamless Create, Read, Update, and Delete operations on FHIR resources.

### FHIR Configuration
- **FHIR Base URL**: https://google-fhir.fhir-aggregator.org/
- **FHIR Schema (R5)**: https://hl7.org/fhir/R5

## Quick Start

### Prerequisites
- Docker & Docker Compose
- Git

### Installation

1. Clone the repository:
```bash
git clone https://github.com/calypr/redcap-fhir.git
cd redcap-fhir
```

2. Configure environment variables:
```bash
cp .env.example .env
# Edit .env with your settings
```

3. Start the services:
```bash
docker-compose up -d
```

4. Access REDCap:
- REDCap: http://localhost:8080
- FHIR API: http://localhost:5000

Important: This repository does not include REDCap application source code. Add your licensed REDCap files to the local `redcap/` directory (at minimum, REDCap `index.php`) so Apache can serve the app.

The default `redcap/index.php` included in this repo acts as a lightweight FHIR workspace. It connects server-side to the internal `fhir-api` service, lets you browse supported resource types, inspect returned payloads, and submit generated CRUD forms for those resources.

## Project Structure

```
redcap-fhir/
├── Dockerfile              # REDCap container configuration
├── docker-compose.yml      # Multi-service orchestration
├── docker-compose.dev.yml  # Development-mode overrides
├── .env.example            # Environment configuration template
├── .gitignore              # Git ignore rules
├── README.md               # This file
├── API.md                  # API usage and endpoint reference
├── SCREENSHOTS.md          # REDCap UI screenshots and visual guide
├── CONTRIBUTING.md         # Developer contribution guide
├── TROUBLESHOOTING.md      # Common issues and fixes
├── docs/images/            # Visual mock screenshots and diagrams
├── src/                    # Application source code
│   ├── app.py             # Main FHIR CRUD API
│   ├── fhir_client.py     # FHIR client for data operations
│   ├── requirements.txt    # Python dependencies
│   └── config.py          # Configuration management
├── scripts/               # Utility scripts
│   ├── setup.sh           # Initial setup script
│   ├── init.sh            # Database initialization helper
│   ├── health-check.sh    # Service health verification
│   ├── reset-db.sh        # Database reset helper
│   ├── collect-logs.sh    # Log aggregation helper
│   └── cleanup.sh         # Resource cleanup helper
└── data/                  # Data persistence volume
```

## Configuration

Edit `.env` file with your settings:

```env
# FHIR Configuration
FHIR_BASE_URL=https://google-fhir.fhir-aggregator.org/
SCHEMA_BASE_URL=https://hl7.org/fhir/R5

# REDCap Configuration
REDCAP_VERSION=latest
REDCAP_PORT=8080

# Database Configuration
MYSQL_ROOT_PASSWORD=your_secure_password
MYSQL_DATABASE=redcap
MYSQL_USER=redcap
MYSQL_PASSWORD=your_db_password

# API Configuration
API_PORT=5000
API_DEBUG=false
```

## FHIR CRUD Operations

### Create
```bash
curl -X POST http://localhost:5000/fhir/resources \
  -H "Content-Type: application/fhir+json" \
  -d @resource.json
```

### Read
```bash
curl http://localhost:5000/fhir/resources/{resourceType}/{id}
```

### Update
```bash
curl -X PUT http://localhost:5000/fhir/resources/{resourceType}/{id} \
  -H "Content-Type: application/fhir+json" \
  -d @resource.json
```

### Delete
```bash
curl -X DELETE http://localhost:5000/fhir/resources/{resourceType}/{id}
```

## Services

### REDCap Container
- Main electronic data capture platform
- Port: 8080
- Local bind mount: `./redcap:/var/www/redcap`
- Internal FHIR bridge: `FHIR_API_URL=http://fhir-api:5000`

### MySQL Database
- Data persistence layer
- Port: 3306 (internal)
- Persistent volume: `mysql_data`

### FHIR API Service
- Custom API for FHIR CRUD operations
- Port: 5000
- Built with Flask/FastAPI

## Logs

View service logs:
```bash
docker-compose logs -f
docker-compose logs -f redcap
docker-compose logs -f fhir-api
```

## Development

### Building from source
```bash
docker-compose build --no-cache
```

### Running in development mode
```bash
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up
```

## Troubleshooting

See [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) for common issues and solutions.

## Visual REDCap Guide

See [SCREENSHOTS.md](./SCREENSHOTS.md) for REDCap UI screenshot examples, workflow diagrams, and FHIR mapping references.

## Contributing

See [CONTRIBUTING.md](./CONTRIBUTING.md).

## License

[Specify your license]

## Support

For issues and questions, please open an issue on GitHub.

## References

- [REDCap Documentation](https://projectredcap.org/)
- [FHIR Specification R5](https://hl7.org/fhir/R5/)
- [FHIR-Aggregator](https://fhir-aggregator.org/)
