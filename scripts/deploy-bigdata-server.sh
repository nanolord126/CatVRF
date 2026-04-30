#!/bin/bash
# Big Data Server Deployment Script
# For production/server deployment (native install)

set -e

echo "=== CatVRF Big Data Server Deployment ==="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
CLICKHOUSE_HOST=${CLICKHOUSE_HOST:-localhost}
CLICKHOUSE_PORT=${CLICKHOUSE_PORT:-9000}
CLICKHOUSE_HTTP_PORT=${CLICKHOUSE_HTTP_PORT:-8123}
CLICKHOUSE_USER=${CLICKHOUSE_USER:-default}
CLICKHOUSE_PASSWORD=${CLICKHOUSE_PASSWORD:-""}
CLICKHOUSE_DATABASE=${CLICKHOUSE_DATABASE:-catvrf_bigdata}

echo -e "${YELLOW}Configuration:${NC}"
echo "ClickHouse Host: $CLICKHOUSE_HOST"
echo "ClickHouse Port: $CLICKHOUSE_PORT"
echo "ClickHouse Database: $CLICKHOUSE_DATABASE"
echo ""

# Step 1: Check ClickHouse connection
echo -e "${YELLOW}[1/5] Checking ClickHouse connection...${NC}"
if command -v clickhouse-client &> /dev/null; then
    echo "ClickHouse client found"
    clickhouse-client --host $CLICKHOUSE_HOST --port $CLICKHOUSE_PORT --query "SELECT 1" > /dev/null 2>&1 && echo -e "${GREEN}✓ ClickHouse connection successful${NC}" || echo -e "${RED}✗ ClickHouse connection failed${NC}"
else
    echo -e "${RED}✗ ClickHouse client not found. Install with: sudo apt-get install clickhouse-client${NC}"
    exit 1
fi

# Step 2: Create database
echo -e "${YELLOW}[2/5] Creating ClickHouse database...${NC}"
clickhouse-client --host $CLICKHOUSE_HOST --port $CLICKHOUSE_PORT --query "CREATE DATABASE IF NOT EXISTS $CLICKHOUSE_DATABASE"
echo -e "${GREEN}✓ Database created${NC}"

# Step 3: Run Big Data schema migrations
echo -e "${YELLOW}[3/5] Running Big Data schema migrations...${NC}"
clickhouse-client --host $CLICKHOUSE_HOST --port $CLICKHOUSE_PORT --database $CLICKHOUSE_DATABASE < database/clickhouse/bigdata_schema.sql
echo -e "${GREEN}✓ Schema migrations completed${NC}"

# Step 4: Check Redis
echo -e "${YELLOW}[4/5] Checking Redis connection...${NC}"
if command -v redis-cli &> /dev/null; then
    redis-cli ping > /dev/null 2>&1 && echo -e "${GREEN}✓ Redis connection successful${NC}" || echo -e "${RED}✗ Redis connection failed${NC}"
else
    echo -e "${YELLOW}⚠ Redis client not found. Install with: sudo apt-get install redis-tools${NC}"
fi

# Step 5: Check PHP extensions
echo -e "${YELLOW}[5/5] Checking PHP extensions...${NC}"
php -m | grep -q pdo && echo -e "${GREEN}✓ PDO extension found${NC}" || echo -e "${RED}✗ PDO extension missing${NC}"
php -m | grep -q rdkafka && echo -e "${GREEN}✓ RDKafka extension found${NC}" || echo -e "${YELLOW}⚠ RDKafka extension missing (install for Kafka support)${NC}"

echo ""
echo -e "${GREEN}=== Deployment completed ===${NC}"
echo ""
echo "Next steps:"
echo "1. Update .env with your server configuration"
echo "2. Configure Kafka if needed (see docs/BIGDATA_SETUP.md)"
echo "3. Set up scheduled jobs for Spark pipelines"
echo "4. Configure monitoring (Prometheus + Grafana)"
echo ""
echo "Example .env configuration:"
echo "CLICKHOUSE_HOST=$CLICKHOUSE_HOST"
echo "CLICKHOUSE_PORT=$CLICKHOUSE_PORT"
echo "CLICKHOUSE_DATABASE=$CLICKHOUSE_DATABASE"
echo "CLICKHOUSE_USER=$CLICKHOUSE_USER"
echo "CLICKHOUSE_PASSWORD=$CLICKHOUSE_PASSWORD"
