#!/bin/bash
# Azure Container Instances Redis Cluster Deployment
# Production 2026 - CatVRF (28 Verticals)
# Author: Sensei (ex-Amazon, Alibaba, Ozon)

set -e

# Configuration
RESOURCE_GROUP="catvrf-prod-rg"
LOCATION="eastus"
REDIS_PASSWORD="${REDIS_PASSWORD:-SuperSecretPass123!}"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}=== Deploying Redis Cluster to Azure Container Instances ===${NC}"
echo ""

# Check Azure CLI
if ! command -v az &> /dev/null; then
    echo "Error: Azure CLI not installed"
    exit 1
fi

# Check if logged in
az account show &> /dev/null || {
    echo "Error: Not logged into Azure. Run 'az login'"
    exit 1
}

echo -e "${YELLOW}Step 1: Creating resource group...${NC}"
az group create \
  --name $RESOURCE_GROUP \
  --location $LOCATION

echo -e "${YELLOW}Step 2: Creating Azure File Share for persistence...${NC}"
STORAGE_ACCOUNT=$(az storage account create \
  --name catvrfredis$(date +%s) \
  --resource-group $RESOURCE_GROUP \
  --location $LOCATION \
  --sku Standard_LRS \
  --query 'name' \
  -o tsv)

STORAGE_KEY=$(az storage account keys list \
  --account-name $STORAGE_ACCOUNT \
  --resource-group $RESOURCE_GROUP \
  --query '[0].value' \
  -o tsv)

# Create file shares for each node
for i in {1..6}; do
  az storage share create \
    --name redis-node-$i \
    --account-name $STORAGE_ACCOUNT \
    --account-key $STORAGE_KEY
done

echo -e "${YELLOW}Step 3: Creating container instances...${NC}"

# Create container group with all 6 nodes
for i in {1..6}; do
  PORT=$((7000 + i))
  BUS_PORT=$((17000 + i))
  
  az container create \
    --resource-group $RESOURCE_GROUP \
    --name redis-node-$i \
    --image redis:7.2-alpine \
    --command-line "redis-server --cluster-enabled yes --cluster-node-timeout 5000 --appendonly yes --port $PORT --requirepass $REDIS_PASSWORD --masterauth $REDIS_PASSWORD" \
    --ports $PORT $BUS_PORT \
    --dns-name-label catvrf-redis-node-$i-$(date +%s) \
    --environment-variables REDIS_PASSWORD=$REDIS_PASSWORD \
    --azure-file-volume-account-name $STORAGE_ACCOUNT \
    --azure-file-volume-account-key $STORAGE_KEY \
    --azure-file-volume-share-name redis-node-$i \
    --azure-file-volume-mount-path /data \
    --cpu 1 \
    --memory 4 \
    --restart-policy Always
done

echo -e "${YELLOW}Step 4: Creating Redis Exporter...${NC}"
az container create \
  --resource-group $RESOURCE_GROUP \
  --name redis-exporter \
  --image oliver006/redis_exporter:latest \
  --environment-variables REDIS_ADDR=redis://$(az container show --resource-group $RESOURCE_GROUP --name redis-node-1 --query ipAddress.fqdn -o tsv):7001 REDIS_PASSWORD=$REDIS_PASSWORD \
  --ports 9121 \
  --dns-name-label catvrf-redis-exporter-$(date +%s) \
  --cpu 0.5 \
  --memory 0.5

echo -e "${YELLOW}Step 5: Getting container IPs...${NC}"
NODE_IPS=""
for i in {1..6}; do
  IP=$(az container show \
    --resource-group $RESOURCE_GROUP \
    --name redis-node-$i \
    --query ipAddress.ip \
    -o tsv)
  PORT=$((7000 + i))
  NODE_IPS="$NODE_IPS $IP:$PORT"
  echo "Node $i: $IP:$PORT"
done

echo -e "${GREEN}=== Deployment Complete ===${NC}"
echo ""
echo "Next steps:"
echo "1. Initialize cluster:"
echo "   redis-cli --cluster create$NODE_IPS --cluster-replicas 1 -a $REDIS_PASSWORD"
echo ""
echo "2. Update Laravel .env:"
echo "   REDIS_CLUSTER_URL=redis://:$REDIS_PASSWORD$NODE_IPS"
echo ""
echo "3. Monitor containers:"
echo "   az container logs --resource-group $RESOURCE_GROUP --name redis-node-1 --follow"
