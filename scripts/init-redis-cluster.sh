#!/bin/bash
# Redis Cluster Initialization Script for CatVRF 2026
# Author: Sensei (ex-Amazon, Alibaba, Ozon)
# Description: Initialize Redis Cluster with 6 nodes (3 master + 3 replica)

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
REDIS_PASSWORD="${REDIS_PASSWORD:-SuperSecretPass123!}"
CLUSTER_REPLICAS=1
NODE_PORTS=(7001 7002 7003 7004 7005 7006)
REDIS_HOST="${REDIS_HOST:-127.0.0.1}"

echo -e "${GREEN}=== CatVRF Redis Cluster Initialization ===${NC}"
echo ""

# Check if redis-cli is available
if ! command -v redis-cli &> /dev/null; then
    echo -e "${RED}Error: redis-cli not found${NC}"
    echo "Install: sudo apt-get install redis-server redis-tools"
    exit 1
fi

# Check if all nodes are running
echo -e "${YELLOW}Checking if all Redis nodes are running...${NC}"
ALL_RUNNING=true
for port in "${NODE_PORTS[@]}"; do
    if redis-cli -h "$REDIS_HOST" -p "$port" -a "$REDIS_PASSWORD" ping 2>/dev/null | grep -q PONG; then
        echo -e "${GREEN}✓ Node on port ${port} is running${NC}"
    else
        echo -e "${RED}✗ Node on port ${port} is NOT running${NC}"
        ALL_RUNNING=false
    fi
done

if [ "$ALL_RUNNING" = false ]; then
    echo -e "${RED}Error: Not all Redis nodes are running. Start cluster first:${NC}"
    echo "  bash k8s/redis-cluster/deploy.sh"
    exit 1
fi

echo ""

# Build node addresses
NODE_IPS=()
for port in "${NODE_PORTS[@]}"; do
    NODE_IPS+=("${REDIS_HOST}:${port}")
done

# Check if cluster is already initialized
echo -e "${YELLOW}Checking if cluster is already initialized...${NC}"
if redis-cli -h "$REDIS_HOST" -p 7001 -a "$REDIS_PASSWORD" cluster info 2>/dev/null | grep -q "cluster_state:ok"; then
    echo -e "${GREEN}✓ Cluster is already initialized${NC}"
    echo ""
    echo -e "${YELLOW}Cluster status:${NC}"
    redis-cli -h "$REDIS_HOST" -p 7001 -a "$REDIS_PASSWORD" cluster info 2>/dev/null
    echo ""
    echo -e "${YELLOW}Cluster nodes:${NC}"
    redis-cli -h "$REDIS_HOST" -p 7001 -a "$REDIS_PASSWORD" cluster nodes 2>/dev/null
    exit 0
fi

# Initialize the cluster
echo -e "${YELLOW}Initializing Redis Cluster...${NC}"
echo "This will create a cluster with ${#NODE_IPS[@]} nodes (3 master + 3 replica)"
echo ""

# Build the create command
CREATE_CMD="redis-cli --cluster create"
for ip_port in "${NODE_IPS[@]}"; do
    CREATE_CMD+=" $ip_port"
done
CREATE_CMD+=" --cluster-replicas $CLUSTER_REPLICAS -a $REDIS_PASSWORD"

# Execute the cluster creation
echo -e "${YELLOW}Executing cluster creation command...${NC}"
echo "$CREATE_CMD"
echo ""

$CREATE_CMD --cluster-yes

echo ""

# Verify cluster state
echo -e "${YELLOW}Verifying cluster state...${NC}"
sleep 2
if redis-cli -h "$REDIS_HOST" -p 7001 -a "$REDIS_PASSWORD" cluster info 2>/dev/null | grep -q "cluster_state:ok"; then
    echo -e "${GREEN}✓ Cluster initialized successfully!${NC}"
else
    echo -e "${RED}✗ Cluster initialization failed${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}=== Cluster Initialization Complete ===${NC}"
echo ""
echo -e "${YELLOW}Cluster Information:${NC}"
redis-cli -h "$REDIS_HOST" -p 7001 -a "$REDIS_PASSWORD" cluster info 2>/dev/null
echo ""
echo -e "${YELLOW}Cluster Nodes:${NC}"
redis-cli -h "$REDIS_HOST" -p 7001 -a "$REDIS_PASSWORD" cluster nodes 2>/dev/null
echo ""
echo -e "${GREEN}=== Next Steps ===${NC}"
echo "1. Update your .env file:"
echo "   REDIS_CLUSTER_ENABLED=true"
echo "   REDIS_CLUSTER_URL=redis://:${REDIS_PASSWORD}@$(echo ${NODE_IPS[*]} | tr ' ' ',')"
echo ""
echo "2. Restart your application:"
echo "   php artisan cache:clear"
echo "   php artisan config:clear"
echo ""
echo "3. Test the cluster:"
echo "   redis-cli -p 7001 -a ${REDIS_PASSWORD} cluster check"
echo ""
echo "4. Monitor cluster health:"
echo "   redis-cli -p 7001 -a ${REDIS_PASSWORD} cluster info"
echo ""
echo -e "${GREEN}Redis Cluster is ready for production use!${NC}"
