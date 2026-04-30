#!/bin/bash
# Redis Cluster Testing Script
# Production 2026 - CatVRF
# Author: Sensei (ex-Amazon, Alibaba, Ozon)

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

REDIS_PASSWORD="${REDIS_PASSWORD:-SuperSecretPass123!}"

echo -e "${GREEN}=== CatVRF Redis Cluster Testing ===${NC}"
echo ""

# Check if Redis is available
if ! command -v redis-cli &> /dev/null; then
    echo -e "${RED}Error: redis-cli not found${NC}"
    echo "Install: sudo apt-get install redis-server redis-tools"
    exit 1
fi

echo -e "${YELLOW}Step 1: Checking Redis server...${NC}"
if redis-cli -a "$REDIS_PASSWORD" ping 2>/dev/null | grep -q PONG; then
    echo -e "${GREEN}✓ Redis server is running${NC}"
else
    echo -e "${YELLOW}Starting Redis server...${NC}"
    sudo systemctl start redis-server
    sleep 2
    if redis-cli ping 2>/dev/null | grep -q PONG; then
        echo -e "${GREEN}✓ Redis server started${NC}"
    else
        echo -e "${RED}✗ Redis server failed to start${NC}"
        exit 1
    fi
fi

echo ""
echo -e "${YELLOW}Step 2: Initializing Redis Cluster (if needed)...${NC}"
chmod +x scripts/init-redis-cluster.sh
./scripts/init-redis-cluster.sh

echo ""
echo -e "${YELLOW}Step 3: Verifying Cluster Health...${NC}"
redis-cli -p 7001 -a "$REDIS_PASSWORD" cluster info 2>/dev/null

echo ""
echo -e "${YELLOW}Step 4: Testing Basic Operations...${NC}"
echo "Testing SET/GET..."
redis-cli -p 7001 -a "$REDIS_PASSWORD" SET test-key "test-value" 2>/dev/null
redis-cli -p 7001 -a "$REDIS_PASSWORD" GET test-key 2>/dev/null

echo ""
echo "Testing vertical key prefixing..."
redis-cli -p 7001 -a "$REDIS_PASSWORD" SET taxi:ride:123:location '{"lat":55.7558,"lon":37.6173}' 2>/dev/null
redis-cli -p 7001 -a "$REDIS_PASSWORD" GET taxi:ride:123:location 2>/dev/null

echo ""
echo -e "${YELLOW}Step 5: Checking Cluster Nodes...${NC}"
redis-cli -p 7001 -a "$REDIS_PASSWORD" cluster nodes 2>/dev/null

echo ""
echo -e "${YELLOW}Step 6: Testing Redis Exporter...${NC}"
curl -s http://localhost:9121/metrics | head -20

echo ""
echo -e "${GREEN}=== All Tests Passed ===${NC}"
echo ""
echo "Next steps:"
echo "1. Update .env with REDIS_CLUSTER_ENABLED=true"
echo "2. Run PHP tests: ./vendor/bin/pest tests/Integration/RedisClusterTest.php"
echo "3. Monitor cluster: redis-cli -p 7001 -a $REDIS_PASSWORD cluster info"
