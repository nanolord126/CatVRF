#!/bin/bash
# Kubernetes Redis Cluster Deployment Script
# Production 2026 - CatVRF (28 Verticals)
# Author: Sensei (ex-Amazon, Alibaba, Ozon)

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
NAMESPACE="catvrf-prod"
REDIS_PASSWORD="${REDIS_PASSWORD:-SuperSecretPass123!}"

echo -e "${GREEN}=== CatVRF Redis Cluster Kubernetes Deployment ===${NC}"
echo ""

# Check kubectl
if ! command -v kubectl &> /dev/null; then
    echo -e "${RED}Error: kubectl is not installed${NC}"
    exit 1
fi

# Check cluster connection
echo -e "${YELLOW}Checking Kubernetes cluster connection...${NC}"
if ! kubectl cluster-info &> /dev/null; then
    echo -e "${RED}Error: Cannot connect to Kubernetes cluster${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Connected to cluster${NC}"
echo ""

# Deploy in order
echo -e "${YELLOW}Step 1: Creating namespace...${NC}"
kubectl apply -f namespace.yaml
echo -e "${GREEN}✓ Namespace created${NC}"
echo ""

echo -e "${YELLOW}Step 2: Creating Secret...${NC}"
# Update secret with actual password
kubectl create secret generic redis-cluster-secret \
  --namespace=$NAMESPACE \
  --from-literal=redis-password="$REDIS_PASSWORD" \
  --dry-run=client -o yaml | kubectl apply -f -
echo -e "${GREEN}✓ Secret created${NC}"
echo ""

echo -e "${YELLOW}Step 3: Creating ConfigMap...${NC}"
kubectl apply -f configmap.yaml
echo -e "${GREEN}✓ ConfigMap created${NC}"
echo ""

echo -e "${YELLOW}Step 4: Creating StorageClass (if needed)...${NC}"
# Check if StorageClass exists, if not create it
if ! kubectl get storageclass ssd &> /dev/null; then
    echo "Creating ssd StorageClass..."
    kubectl apply -f storageclass.yaml
    echo -e "${GREEN}✓ StorageClass created${NC}"
else
    echo -e "${GREEN}✓ StorageClass already exists${NC}"
fi
echo ""

echo -e "${YELLOW}Step 5: Creating Headless Service...${NC}"
kubectl apply -f service.yaml
echo -e "${GREEN}✓ Headless Service created${NC}"
echo ""

echo -e "${YELLOW}Step 6: Deploying StatefulSet (this may take a few minutes)...${NC}"
kubectl apply -f statefulset.yaml
echo -e "${GREEN}✓ StatefulSet deployed${NC}"
echo ""

# Wait for StatefulSet to be ready
echo -e "${YELLOW}Waiting for StatefulSet pods to be ready...${NC}"
kubectl wait --for=condition=ready pod -l app=redis-cluster -n $NAMESPACE --timeout=600s
echo -e "${GREEN}✓ All pods are ready${NC}"
echo ""

echo -e "${YELLOW}Step 7: Initializing Redis Cluster...${NC}"
kubectl apply -f init-job.yaml
echo -e "${GREEN}✓ Init Job submitted${NC}"
echo ""

# Wait for init job to complete
echo -e "${YELLOW}Waiting for cluster initialization...${NC}"
kubectl wait --for=condition=complete job/redis-cluster-init -n $NAMESPACE --timeout=300s
echo -e "${GREEN}✓ Cluster initialized${NC}"
echo ""

echo -e "${YELLOW}Step 8: Deploying Redis Exporter...${NC}"
kubectl apply -f exporter.yaml
kubectl apply -f exporter-service.yaml
echo -e "${GREEN}✓ Redis Exporter deployed${NC}"
echo ""

echo -e "${YELLOW}Step 9: Deploying HPA...${NC}"
kubectl apply -f hpa.yaml
echo -e "${GREEN}✓ HPA deployed${NC}"
echo ""

echo -e "${YELLOW}Step 10: Creating PodDisruptionBudget...${NC}"
kubectl apply -f poddisruptionbudget.yaml
echo -e "${GREEN}✓ PodDisruptionBudget created${NC}"
echo ""

echo -e "${YELLOW}Step 11: Deploying Prometheus Rules (if Prometheus Operator is installed)...${NC}"
if kubectl get crd prometheusrules.monitoring.coreos.com &> /dev/null; then
    kubectl apply -f prometheus-rules.yaml
    echo -e "${GREEN}✓ Prometheus Rules deployed${NC}"
else
    echo -e "${YELLOW}⚠ Prometheus Operator not found, skipping Prometheus Rules${NC}"
fi
echo ""

# Display cluster status
echo -e "${GREEN}=== Deployment Complete ===${NC}"
echo ""
echo -e "${YELLOW}Cluster Status:${NC}"
kubectl get pods -n $NAMESPACE -l app=redis-cluster
echo ""
echo -e "${YELLOW}Cluster Info:${NC}"
kubectl exec -n $NAMESPACE redis-cluster-0 -- redis-cli -a "$REDIS_PASSWORD" cluster info
echo ""
echo -e "${YELLOW}Cluster Nodes:${NC}"
kubectl exec -n $NAMESPACE redis-cluster-0 -- redis-cli -a "$REDIS_PASSWORD" cluster nodes
echo ""

echo -e "${GREEN}=== Next Steps ===${NC}"
echo ""
echo "1. Update your Laravel .env:"
echo "   REDIS_CLUSTER_ENABLED=true"
echo "   REDIS_CLUSTER_URL=redis://:${REDIS_PASSWORD}@redis-cluster-0.redis-cluster.${NAMESPACE}.svc.cluster.local:6379,redis-cluster-1.redis-cluster.${NAMESPACE}.svc.cluster.local:6379,..."
echo "   REDIS_PASSWORD=${REDIS_PASSWORD}"
echo ""
echo "2. Verify cluster health:"
echo "   kubectl exec -n $NAMESPACE redis-cluster-0 -- redis-cli -a ${REDIS_PASSWORD} cluster check"
echo ""
echo "3. Monitor logs:"
echo "   kubectl logs -n $NAMESPACE -l app=redis-cluster --tail=100 -f"
echo ""
echo "4. Check metrics:"
echo "   kubectl port-forward -n $NAMESPACE svc/redis-exporter 9121:9121"
echo "   curl http://localhost:9121/metrics"
echo ""
echo -e "${GREEN}Redis Cluster is ready for production use!${NC}"
