#!/bin/bash
# GCP Cloud Run Redis Cluster Deployment
# Production 2026 - CatVRF (28 Verticals)
# Author: Sensei (ex-Amazon, Alibaba, Ozon)

set -e

# Configuration
PROJECT_ID="${PROJECT_ID:-catvrf-prod}"
REGION="${REGION:-us-central1}"
REDIS_PASSWORD="${REDIS_PASSWORD:-SuperSecretPass123!}"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}=== Deploying Redis Cluster to GCP Cloud Run ===${NC}"
echo ""

# Check gcloud CLI
if ! command -v gcloud &> /dev/null; then
    echo "Error: gcloud CLI not installed"
    exit 1
fi

# Set project
gcloud config set project $PROJECT_ID

echo -e "${YELLOW}Step 1: Enabling APIs...${NC}"
gcloud services enable \
  run.googleapis.com \
  redis.googleapis.com \
  compute.googleapis.com \
  --project $PROJECT_ID

echo -e "${YELLOW}Step 2: Creating Memorystore for Redis (recommended for production)...${NC}"
# For production, use Memorystore instead of Cloud Run
# This creates a managed Redis instance
gcloud redis instances create catvrf-redis-cluster \
  --region=$REGION \
  --size=5 \
  --tier=STANDARD_HA \
  --redis-version=7.2 \
  --display-name="CatVRF Redis Cluster" \
  --project=$PROJECT_ID

echo -e "${YELLOW}Step 3: Waiting for Memorystore to be ready...${NC}"
# This may take several minutes
gcloud redis instances describe catvrf-redis-cluster \
  --region=$REGION \
  --format="value(state)" \
  --project $PROJECT_ID | grep -q "READY" || {
  echo "Memorystore is provisioning. This may take 10-15 minutes..."
  sleep 600
}

echo -e "${YELLOW}Step 4: Getting Memorystore connection details...${NC}"
REDIS_IP=$(gcloud redis instances describe catvrf-redis-cluster \
  --region=$REGION \
  --format="value(host)" \
  --project $PROJECT_ID)

REDIS_PORT=$(gcloud redis instances describe catvrf-redis-cluster \
  --region=$REGION \
  --format="value(port)" \
  --project $PROJECT_ID)

echo -e "${YELLOW}Step 5: Configuring VPC connector...${NC}"
# Create serverless VPC access connector
gcloud compute networks vpc-access connectors create catvrf-redis-connector \
  --region=$REGION \
  --range=10.8.0.0/28 \
  --project $PROJECT_ID || true

echo -e "${YELLOW}Step 6: Creating Redis Exporter as Cloud Run service...${NC}"
# Create a simple Cloud Run service for Redis Exporter
gcloud run deploy catvrf-redis-exporter \
  --image=oliver006/redis_exporter:latest \
  --platform=managed \
  --region=$REGION \
  --allow-unauthenticated \
  --set-env-vars="REDIS_ADDR=redis://$REDIS_IP:$REDIS_PORT,REDIS_PASSWORD=$REDIS_PASSWORD" \
  --port=9121 \
  --memory=256Mi \
  --cpu=1 \
  --project $PROJECT_ID

EXPORTER_URL=$(gcloud run services describe catvrf-redis-exporter \
  --platform=managed \
  --region=$REGION \
  --format="value(status.url)" \
  --project $PROJECT_ID)

echo -e "${GREEN}=== Deployment Complete ===${NC}"
echo ""
echo "Memorystore IP: $REDIS_IP"
echo "Memorystore Port: $REDIS_PORT"
echo "Exporter URL: $EXPORTER_URL"
echo ""
echo "Next steps:"
echo "1. Configure VPC peering for your Cloud Run services"
echo "2. Update Laravel .env:"
echo "   REDIS_HOST=$REDIS_IP"
echo "   REDIS_PORT=$REDIS_PORT"
echo "   REDIS_PASSWORD=$REDIS_PASSWORD"
echo ""
echo "3. Test connection:"
echo "   redis-cli -h $REDIS_IP -p $REDIS_PORT -a $REDIS_PASSWORD ping"
echo ""
echo "4. View metrics:"
echo "   curl $EXPORTER_URL/metrics"
echo ""
echo "Note: For Redis Cluster mode on GCP, use GKE (Kubernetes) instead of Cloud Run."
echo "See k8s/redis-cluster/ for Kubernetes deployment."
