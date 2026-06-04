#!/bin/bash

# setup.sh - Initial setup script for REDCap FHIR integration

set -e

echo "=========================================="
echo "REDCap FHIR Integration Setup"
echo "=========================================="

# Check if .env file exists
if [ ! -f .env ]; then
    echo "Creating .env file from .env.example..."
    cp .env.example .env
    echo "✓ .env file created. Please update it with your configuration."
else
    echo "✓ .env file already exists"
fi

# Create necessary directories
echo ""
echo "Creating directories..."
mkdir -p logs
mkdir -p data
mkdir -p scripts
mkdir -p docker
chmod 755 scripts/*.sh 2>/dev/null || true

echo "✓ Directories created"

# Load environment variables
export $(cat .env | grep -v '#' | xargs)

# Build Docker images
echo ""
echo "Building Docker images..."
docker-compose build

echo "✓ Docker images built successfully"

# Start services
echo ""
echo "Starting services..."
docker-compose up -d

echo "✓ Services started"

# Wait for MySQL to be ready
echo ""
echo "Waiting for MySQL to be ready..."
max_attempts=30
attempt=1

while [ $attempt -le $max_attempts ]; do
    if docker-compose exec -T mysql mysqladmin ping -h localhost -u root -p"${MYSQL_ROOT_PASSWORD}" &> /dev/null; then
        echo "✓ MySQL is ready"
        break
    fi
    
    echo "  Attempt $attempt/$max_attempts..."
    sleep 2
    attempt=$((attempt + 1))
done

if [ $attempt -gt $max_attempts ]; then
    echo "✗ MySQL failed to start within timeout"
    exit 1
fi

# Initialize database
echo ""
echo "Initializing database..."
docker-compose exec -T mysql mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "USE redcap;" &> /dev/null || true

echo "✓ Database initialized"

# Test FHIR API
echo ""
echo "Testing FHIR API..."
sleep 5

FHIR_API_HEALTH=$(curl -s http://localhost:5000/health || echo "failed")

if echo "$FHIR_API_HEALTH" | grep -q "healthy"; then
    echo "✓ FHIR API is healthy"
else
    echo "✗ FHIR API health check failed"
    echo "  Response: $FHIR_API_HEALTH"
fi

echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Access points:"
echo "  - REDCap:    http://localhost:8080"
echo "  - FHIR API:  http://localhost:5000"
echo "  - Health:    http://localhost:5000/health"
echo ""
echo "To view logs:"
echo "  docker-compose logs -f"
echo ""
echo "To stop services:"
echo "  docker-compose down"
echo ""
