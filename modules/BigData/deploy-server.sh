#!/bin/bash
# Big Data Server Deployment Script
# For production/server deployment without Docker
# Uses: ClickHouse (WSL/native), Redis Streams, Confluent Cloud REST Proxy

set -e

echo "=== CatVRF Big Data Server Deployment ==="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
CLICKHOUSE_HOST=${CLICKHOUSE_HOST:-localhost}
CLICKHOUSE_HTTP_PORT=${CLICKHOUSE_HTTP_PORT:-8123}
CLICKHOUSE_USER=${CLICKHOUSE_USER:-default}
CLICKHOUSE_PASSWORD=${CLICKHOUSE_PASSWORD:-""}
CLICKHOUSE_DATABASE=${CLICKHOUSE_DATABASE:-catvrf_bigdata}
KAFKA_MODE=${KAFKA_MODE:-redis_streams}

echo -e "${YELLOW}Configuration:${NC}"
echo "ClickHouse Host: $CLICKHOUSE_HOST"
echo "ClickHouse HTTP Port: $CLICKHOUSE_HTTP_PORT"
echo "ClickHouse Database: $CLICKHOUSE_DATABASE"
echo "Event Streaming Mode: $KAFKA_MODE"
echo ""

# Step 1: Check ClickHouse connection (HTTP interface)
echo -e "${YELLOW}[1/6] Checking ClickHouse HTTP connection...${NC}"
if command -v curl &> /dev/null; then
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://$CLICKHOUSE_HOST:$CLICKHOUSE_HTTP_PORT/ping" --connect-timeout 5 2>/dev/null || echo "000")
    if [ "$HTTP_STATUS" = "200" ]; then
        VERSION=$(curl -s "http://$CLICKHOUSE_HOST:$CLICKHOUSE_HTTP_PORT/?query=SELECT+version()" 2>/dev/null)
        echo -e "${GREEN}✓ ClickHouse connected (version: $VERSION)${NC}"
    else
        echo -e "${RED}✗ ClickHouse HTTP connection failed (status: $HTTP_STATUS)${NC}"
        echo "  Start ClickHouse: sudo -u clickhouse /usr/bin/clickhouse server --config-file /etc/clickhouse-server/config.xml &"
        exit 1
    fi
else
    echo -e "${YELLOW}⚠ curl not found, trying clickhouse-client...${NC}"
    if command -v clickhouse-client &> /dev/null; then
        clickhouse-client --query "SELECT 1" > /dev/null 2>&1 && echo -e "${GREEN}✓ ClickHouse connected${NC}" || echo -e "${RED}✗ ClickHouse connection failed${NC}"
    else
        echo -e "${RED}✗ Neither curl nor clickhouse-client found${NC}"
        exit 1
    fi
fi

# Step 2: Create database
echo -e "${YELLOW}[2/6] Creating ClickHouse database...${NC}"
if command -v clickhouse-client &> /dev/null; then
    clickhouse-client --query "CREATE DATABASE IF NOT EXISTS $CLICKHOUSE_DATABASE"
else
    curl -s "http://$CLICKHOUSE_HOST:$CLICKHOUSE_HTTP_PORT/?query=CREATE+DATABASE+IF+NOT+EXISTS+$CLICKHOUSE_DATABASE" > /dev/null
fi
echo -e "${GREEN}✓ Database '$CLICKHOUSE_DATABASE' created${NC}"

# Step 3: Run migrations
echo -e "${YELLOW}[3/6] Running Big Data schema migrations...${NC}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCHEMA_FILE="$SCRIPT_DIR/../../database/clickhouse/bigdata_schema.sql"

if [ -f "$SCHEMA_FILE" ]; then
    if command -v clickhouse-client &> /dev/null; then
        clickhouse-client --database "$CLICKHOUSE_DATABASE" --queries-file "$SCHEMA_FILE"
    else
        QUERY=$(cat "$SCHEMA_FILE" | python3 -c "import urllib.parse,sys; print(urllib.parse.quote(sys.stdin.read()))" 2>/dev/null || cat "$SCHEMA_FILE")
        curl -s "http://$CLICKHOUSE_HOST:$CLICKHOUSE_HTTP_PORT/?database=$CLICKHOUSE_DATABASE" --data-binary @"$SCHEMA_FILE" > /dev/null
    fi
    echo -e "${GREEN}✓ Schema migrations completed${NC}"
else
    echo -e "${RED}✗ Schema file not found: $SCHEMA_FILE${NC}"
    exit 1
fi

# Step 4: Verify tables
echo -e "${YELLOW}[4/6] Verifying tables...${NC}"
if command -v clickhouse-client &> /dev/null; then
    TABLE_COUNT=$(clickhouse-client --database "$CLICKHOUSE_DATABASE" --query "SELECT count() FROM system.tables WHERE database='$CLICKHOUSE_DATABASE'")
else
    TABLE_COUNT=$(curl -s "http://$CLICKHOUSE_HOST:$CLICKHOUSE_HTTP_PORT/?database=$CLICKHOUSE_DATABASE&query=SELECT+count()+FROM+system.tables+WHERE+database='$CLICKHOUSE_DATABASE'")
fi
echo -e "${GREEN}✓ $TABLE_COUNT tables created${NC}"

# Step 5: Check Redis (for event streaming)
echo -e "${YELLOW}[5/6] Checking Redis connection...${NC}"
if command -v redis-cli &> /dev/null; then
    redis-cli ping > /dev/null 2>&1 && echo -e "${GREEN}✓ Redis connected (mode: $KAFKA_MODE)${NC}" || echo -e "${YELLOW}⚠ Redis not available — install: sudo apt-get install redis-server${NC}"
elif [ "$KAFKA_MODE" = "redis_streams" ]; then
    echo -e "${YELLOW}⚠ redis-cli not found. For Redis Streams mode, install Redis${NC}"
    echo "  sudo apt-get install redis-server redis-tools"
fi

# Step 6: Check PHP
echo -e "${YELLOW}[6/6] Checking PHP...${NC}"
php -v > /dev/null 2>&1 && echo -e "${GREEN}✓ PHP found$(php -v 2>/dev/null | head -1 | awk '{print " ("$2")"}')${NC}" || echo -e "${RED}✗ PHP not found${NC}"

echo ""
echo -e "${GREEN}=== Deployment completed ===${NC}"
echo ""
echo "Infrastructure status:"
echo "  ClickHouse: http://$CLICKHOUSE_HOST:$CLICKHOUSE_HTTP_PORT (database: $CLICKHOUSE_DATABASE)"
echo "  Event Streaming: $KAFKA_MODE"
echo ""
echo "Next steps:"
echo "1. Update .env with your configuration (see .env.example.bigdata)"
echo "2. For Confluent Cloud: set KAFKA_MODE=rest_proxy and KAFKA_REST_PROXY_URL"
echo "3. For Grafana Cloud: set GRAFANA_URL and GRAFANA_API_KEY"
echo "4. Run consumer: php artisan queue:work bigdata-kafka --daemon"
echo ""
echo "Cloud services (free tiers):"
echo "  Confluent Cloud: https://confluent.cloud/ (100K messages/month)"
echo "  ClickHouse Cloud: https://clickhouse.cloud/ (1 service free)"
echo "  Redis Cloud: https://redis.com/try-free/ (30MB free)"
echo "  Grafana Cloud: https://grafana.com/auth/sign-up/cloud/ (3 dashboards free)"
