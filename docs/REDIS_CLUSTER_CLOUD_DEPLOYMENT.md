# Redis Cluster Cloud Deployment Guide for CatVRF 2026

**Author:** Sensei (ex-Amazon, Alibaba, Ozon)  
**Version:** 1.0  
**Last Updated:** 2026-04-26

---

## Overview

This guide covers deploying Redis Cluster to cloud platforms (AWS, Azure, GCP) for CatVRF production environment with 28 verticals.

## Quick Reference

| Platform | Deployment Method | Recommended For |
|----------|------------------|-----------------|
| AWS | ECS Fargate | Production, high availability |
| Azure | Container Instances | Quick deployment, dev/staging |
| GCP | Memorystore | Managed Redis, production |
| Kubernetes | GKE/EKS/AKS | Production, full control |

---

## AWS ECS Deployment

### Prerequisites

```bash
# Install AWS CLI
pip install awscli

# Configure AWS credentials
aws configure

# Enable ECS
aws ecs create-cluster --cluster-name catvrf-prod
```

### Deploy

```bash
cd cloud/aws

# Deploy with default password
./deploy-ecs.sh

# Or with custom password
REDIS_PASSWORD=YourSecurePassword123 ./deploy-ecs.sh
```

### Architecture

- **ECS Fargate** - Serverless containers
- **EFS** - Shared file system for persistence
- **CloudWatch** - Logging and monitoring
- **ALB** - Load balancer (optional)

### Cost Estimate

- **ECS Fargate:** ~$0.05/hr per vCPU
- **EFS:** ~$0.30/GB/month
- **Total:** ~$150-200/month for 6 nodes

---

## Azure Container Instances

### Prerequisites

```bash
# Install Azure CLI
# https://docs.microsoft.com/en-us/cli/azure/install-azure-cli

# Login
az login

# Set subscription
az account set --subscription <subscription-id>
```

### Deploy

```bash
cd cloud/azure

# Deploy
./deploy-aci.sh
```

### Architecture

- **Azure Container Instances** - Container instances
- **Azure File Share** - Persistent storage
- **Azure Monitor** - Monitoring

### Cost Estimate

- **ACI:** ~$0.03/hr per vCPU
- **File Share:** ~$0.06/GB/month
- **Total:** ~$100-150/month for 6 nodes

---

## GCP Cloud Run / Memorystore

### Prerequisites

```bash
# Install gcloud CLI
# https://cloud.google.com/sdk/docs/install

# Login
gcloud auth login

# Set project
gcloud config set project catvrf-prod
```

### Deploy

```bash
cd cloud/gcp

# Deploy
PROJECT_ID=catvrf-prod REGION=us-central1 ./deploy-cloud-run.sh
```

### Architecture

- **Memorystore** - Managed Redis (recommended)
- **Cloud Run** - Serverless exporter
- **VPC Connector** - Private networking

### Cost Estimate

- **Memorystore:** ~$150/month for 5GB
- **Cloud Run:** ~$10/month for exporter
- **Total:** ~$160/month

---

## Kubernetes Cloud Deployment

### AWS EKS

```bash
# Create EKS cluster
eksctl create cluster --name catvrf-prod --region us-east-1

# Deploy Redis Cluster
cd k8s/redis-cluster
kubectl apply -f namespace.yaml
kubectl apply -f secret.yaml
kubectl apply -f configmap.yaml
kubectl apply -f statefulset.yaml
kubectl apply -f service.yaml
kubectl apply -f init-job.yaml
```

### Azure AKS

```bash
# Create AKS cluster
az aks create \
  --resource-group catvrf-prod-rg \
  --name catvrf-prod \
  --node-count 3 \
  --node-vm-size Standard_D4s_v3

# Get credentials
az aks get-credentials \
  --resource-group catvrf-prod-rg \
  --name catvrf-prod

# Deploy
cd k8s/redis-cluster
kubectl apply -f .
```

### GCP GKE

```bash
# Create GKE cluster
gcloud container clusters create catvrf-prod \
  --region us-central1 \
  --num-nodes 3 \
  --machine-type e2-standard-4

# Get credentials
gcloud container clusters get-credentials catvrf-prod \
  --region us-central1

# Deploy
cd k8s/redis-cluster
kubectl apply -f .
```

---

## Terraform Deployment

### Local Docker

```bash
cd terraform

# Initialize
terraform init

# Plan
terraform plan -var="redis_password=YourSecurePassword123"

# Apply
terraform apply -var="redis_password=YourSecurePassword123"

# Initialize cluster
terraform output init_command | bash
```

### AWS with Terraform

Create `aws-redis-cluster.tf`:

```hcl
provider "aws" {
  region = "us-east-1"
}

resource "aws_ecs_cluster" "main" {
  name = "catvrf-redis-cluster"
}

# Add ECS resources...
```

---

## Cloud-Specific Configuration

### Laravel .env for AWS

```env
REDIS_CLUSTER_ENABLED=true
REDIS_CLUSTER_URL=redis://:password@node1-ip:7001,node2-ip:7002,...
REDIS_PASSWORD=YourSecurePassword123
REDIS_CLIENT=phpredis
```

### Laravel .env for Azure

```env
REDIS_CLUSTER_ENABLED=true
REDIS_CLUSTER_URL=redis://:password@node1.eastus.azurecontainer.io:7001,...
REDIS_PASSWORD=YourSecurePassword123
```

### Laravel .env for GCP

```env
REDIS_HOST=10.x.x.x  # Memorystore private IP
REDIS_PORT=6379
REDIS_PASSWORD=YourSecurePassword123
# Note: Memorystore is standalone, not cluster mode by default
```

---

## Monitoring & Alerting

### AWS CloudWatch

```bash
# Create CloudWatch dashboard
aws cloudwatch put-dashboard \
  --dashboard-name catvrf-redis-cluster \
  --dashboard-body file://cloud/aws/cloudwatch-dashboard.json
```

### Azure Monitor

```bash
# Create Azure Monitor alerts
az monitor metrics alert create \
  --name redis-high-memory \
  --resource-group catvrf-prod-rg \
  --scopes /subscriptions/.../resourceGroups/.../providers/Microsoft.ContainerInstance/containerGroups/redis-node-1 \
  --condition "avg Percentage CPU > 80"
```

### GCP Cloud Monitoring

```bash
# Create monitoring policy
gcloud alpha monitoring policies create \
  --policy-from-file=cloud/gcp/monitoring-policy.yaml
```

---

## Security

### AWS Security Groups

```bash
# Allow only internal traffic
aws ec2 authorize-security-group-ingress \
  --group-id sg-xxx \
  --protocol tcp \
  --port 6379 \
  --source-group sg-xxx
```

### Azure Network Security Groups

```bash
# Create NSG
az network nsg create \
  --resource-group catvrf-prod-rg \
  --name redis-nsg

# Add rule
az network nsg rule create \
  --resource-group catvrf-prod-rg \
  --nsg-name redis-nsg \
  --name allow-internal \
  --priority 100 \
  --source-address-prefixes VirtualNetwork \
  --destination-port-ranges 6379
```

---

## Disaster Recovery

### AWS Backup

```bash
# Enable EFS backup
aws backup put-backup-vault \
  --backup-vault-name catvrf-redis-backup

aws backup put-backup-plan \
  --backup-plan file://cloud/aws/backup-plan.json
```

### Azure Backup

```bash
# Enable Azure Backup
az backup protection enable-for-vm \
  --resource-group catvrf-prod-rg \
  --vm redis-node-1 \
  --policy-name default-policy
```

---

## Cost Optimization

### AWS

- Use **Graviton** instances for 20% cost savings
- Enable **Reserved Instances** for 1-3 year commitments
- Use **Spot Instances** for replicas (dev/staging)

### Azure

- Use **Spot instances** for replicas
- Enable **Azure Hybrid Benefit**
- Use **Reserved Instances** for production

### GCP

- Use **Preemptible VMs** for replicas
- Enable **Committed Use Discounts**
- Use **Sustained Use Discounts**

---

## Troubleshooting

### AWS ECS

```bash
# Check task logs
aws logs tail /ecs/catvrf-redis-cluster --follow

# Describe task
aws ecs describe-tasks --cluster catvrf-prod --tasks <task-id>
```

### Azure ACI

```bash
# View logs
az container logs --resource-group catvrf-prod-rg --name redis-node-1

# Check events
az container show --resource-group catvrf-prod-rg --name redis-node-1 --query instanceView.events
```

### GCP

```bash
# View logs
gcloud logging read "resource.type=gce_instance AND labels.container_name=redis-node-1"

# Check status
gcloud redis instances describe catvrf-redis-cluster --region=us-central1
```

---

## References

- [AWS ECS Documentation](https://docs.aws.amazon.com/ecs/)
- [Azure Container Instances](https://docs.microsoft.com/en-us/azure/container-instances/)
- [GCP Memorystore](https://cloud.google.com/memorystore)
- [Kubernetes StatefulSets](https://kubernetes.io/docs/concepts/workloads/controllers/statefulset/)

---

**Last Updated:** 2026-04-26  
**Status:** Production Ready ✅
