#!/bin/bash
# AWS ECS Redis Cluster Deployment Script
# Production 2026 - CatVRF (28 Verticals)
# Author: Sensei (ex-Amazon, Alibaba, Ozon)

set -e

# Configuration
CLUSTER_NAME="catvrf-prod"
TASK_FAMILY="catvrf-redis-cluster"
SERVICE_NAME="catvrf-redis-cluster"
REGION="us-east-1"
REDIS_PASSWORD="${REDIS_PASSWORD:-SuperSecretPass123!}"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}=== Deploying Redis Cluster to AWS ECS ===${NC}"
echo ""

# Check AWS CLI
if ! command -v aws &> /dev/null; then
    echo "Error: AWS CLI not installed"
    exit 1
fi

# Check if logged in
aws sts get-caller-identity &> /dev/null || {
    echo "Error: Not logged into AWS. Run 'aws configure'"
    exit 1
}

echo -e "${YELLOW}Step 1: Creating EFS filesystem...${NC}"
EFS_ID=$(aws efs create-file-system \
  --creation-token catvrf-redis-cluster \
  --region $REGION \
  --query 'FileSystemId' \
  --output text)

echo "EFS Filesystem ID: $EFS_ID"

# Wait for EFS to be available
echo "Waiting for EFS to be available..."
aws efs describe-file-systems \
  --file-system-id $EFS_ID \
  --region $REGION \
  --query 'FileSystems[0].LifeCycleState' \
  --output text | grep -q "available" || sleep 30

echo -e "${YELLOW}Step 2: Creating mount targets...${NC}"
# Get subnets
SUBNETS=$(aws ec2 describe-subnets \
  --filters "Name=vpc-id,Values=$(aws ec2 describe-vpcs --query 'Vpcs[0].VpcId' --output text)" \
  --query 'Subnets[0:3].SubnetId' \
  --output text \
  --region $REGION)

SUBNET_ARRAY=($SUBNETS)
for SUBNET in "${SUBNET_ARRAY[@]}"; do
  aws efs create-mount-target \
    --file-system-id $EFS_ID \
    --subnet-id $SUBNET \
    --security-group-ids $(aws ec2 describe-security-groups --group-names default --query 'SecurityGroups[0].GroupId' --output text --region $REGION) \
    --region $REGION
done

echo -e "${YELLOW}Step 3: Creating CloudWatch log group...${NC}"
aws logs create-log-group \
  --log-group-name /ecs/catvrf-redis-cluster \
  --region $REGION || true

echo -e "${YELLOW}Step 4: Registering ECS task definition...${NC}"
sed "s/\${REDIS_PASSWORD}/$REDIS_PASSWORD/g; s/\${EFS_FILESYSTEM_ID}/$EFS_ID/g" \
  cloud/aws/ecs-task-definition.json > /tmp/ecs-task-def.json

aws ecs register-task-definition \
  --cli-input-json file:///tmp/ecs-task-def.json \
  --region $REGION

TASK_ARN=$(aws ecs describe-task-definition \
  --task-definition $TASK_FAMILY \
  --query 'taskDefinition.taskDefinitionArn' \
  --output text \
  --region $REGION)

echo -e "${YELLOW}Step 5: Creating ECS service...${NC}"
aws ecs create-service \
  --cluster $CLUSTER_NAME \
  --service-name $SERVICE_NAME \
  --task-definition $TASK_ARN \
  --desired-count 1 \
  --launch-type FARGATE \
  --network-configuration "awsvpcConfiguration={subnets=${SUBNET_ARRAY[0]},securityGroups=$(aws ec2 describe-security-groups --group-names default --query 'SecurityGroups[0].GroupId' --output text --region $REGION),assignPublicIp=ENABLED}" \
  --region $REGION || \
aws ecs update-service \
  --cluster $CLUSTER_NAME \
  --service-name $SERVICE_NAME \
  --task-definition $TASK_ARN \
  --force-new-deployment \
  --region $REGION

echo -e "${YELLOW}Step 6: Waiting for service to stabilize...${NC}"
aws ecs wait services-stable \
  --cluster $CLUSTER_NAME \
  --services $SERVICE_NAME \
  --region $REGION

echo -e "${GREEN}=== Deployment Complete ===${NC}"
echo ""
echo "Service: $SERVICE_NAME"
echo "Task ARN: $TASK_ARN"
echo "EFS ID: $EFS_ID"
echo ""
echo "Next steps:"
echo "1. Get task IP: aws ecs describe-tasks --cluster $CLUSTER_NAME --tasks \$(aws ecs list-tasks --cluster $CLUSTER_NAME --service-name $SERVICE_NAME --query 'taskArns[0]' --output text --region $REGION) --region $REGION"
echo "2. Initialize cluster: redis-cli --cluster create <node-ips> --cluster-replicas 1 -a $REDIS_PASSWORD"
echo "3. Update Laravel .env with cluster IPs"
