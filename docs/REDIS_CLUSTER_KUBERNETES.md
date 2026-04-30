# Redis Cluster Kubernetes Deployment Guide for CatVRF 2026

**Author:** Sensei (ex-Amazon, Alibaba, Ozon)  
**Version:** 1.0  
**Last Updated:** 2026-04-26

---

## Overview

This guide covers deploying Redis Cluster on Kubernetes for CatVRF production environment with 28 verticals and high-throughput WebSocket scaling.

### Why Kubernetes for Redis Cluster?

- **StatefulSet:** Stable network identities and persistent storage
- **Automatic Failover:** Built-in replica promotion
- **Horizontal Scaling:** Easy addition of nodes
- **Self-Healing:** Automatic pod restart and recovery
- **Multi-Zone:** Distribute across availability zones
- **Cloud-Native:** Native integration with cloud providers

---

## Architecture

### Kubernetes Resources

```
┌─────────────────────────────────────────────────────────────┐
│                    Kubernetes Namespace                     │
│                    catvrf-prod                              │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  StatefulSet: redis-cluster (6 replicas)                     │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │ redis-0     │  │ redis-1     │  │ redis-2     │         │
│  │ Master      │  │ Master      │  │ Master      │         │
│  │ Slots 0-5460│  │ 5461-10922 │  │ 10923-16383│         │
│  └─────────────┘  └─────────────┘  └─────────────┘         │
│       │                 │                 │                 │
│       └─────────────────┼─────────────────┘                 │
│                         │                                   │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │ redis-3     │  │ redis-4     │  │ redis-5     │         │
│  │ Replica     │  │ Replica     │  │ Replica     │         │
│  │ of redis-0  │  │ of redis-1  │  │ of redis-2  │         │
│  └─────────────┘  └─────────────┘  └─────────────┘         │
│                                                               │
│  Service: redis-cluster (Headless)                           │
│  DNS: redis-cluster-0.redis-cluster.catvrf-prod.svc...      │
│                                                               │
│  Deployment: redis-exporter                                   │
│  Service: redis-exporter (Port 9121)                         │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

### Storage

- **StorageClass:** `ssd` (configurable per cloud provider)
- **Volume Size:** 50Gi per node
- **Access Mode:** ReadWriteOnce
- **Reclaim Policy:** Retain

---

## Prerequisites

### Requirements

- Kubernetes 1.24+
- kubectl configured
- StorageClass for SSD (gp3, premium-rwo, pd-ssd, or local-ssd)
- 6 nodes with 5Gi RAM each (minimum)
- Network bandwidth for cluster communication

### Cloud Provider Storage

**AWS (EBS gp3):**
```yaml
provisioner: kubernetes.io/aws-ebs
parameters:
  type: gp3
  iops: "3000"
  throughput: "125"
```

**Azure (Premium SSD):**
```yaml
provisioner: disk.csi.azure.com
parameters:
  storageaccounttype: Premium_LRS
```

**GCP (pd-ssd):**
```yaml
provisioner: pd.csi.storage.gke.io
parameters:
  type: pd-ssd
```

**Bare-metal (local-ssd):**
```yaml
provisioner: kubernetes.io/no-provisioner
volumeBindingMode: WaitForFirstConsumer
```

---

## Quick Start

### 1. Deploy Cluster

```bash
cd k8s/redis-cluster

# Make deploy script executable
chmod +x deploy.sh

# Deploy with default password
./deploy.sh

# Or with custom password
REDIS_PASSWORD=YourSecurePassword123 ./deploy.sh
```

### 2. Verify Deployment

```bash
# Check pod status
kubectl get pods -n catvrf-prod -l app=redis-cluster

# Check cluster info
kubectl exec -n catvrf-prod redis-cluster-0 -- redis-cli -a SuperSecretPass123! cluster info

# Check cluster nodes
kubectl exec -n catvrf-prod redis-cluster-0 -- redis-cli -a SuperSecretPass123! cluster nodes

# Check cluster slots
kubectl exec -n catvrf-prod redis-cluster-0 -- redis-cli -a SuperSecretPass123! cluster slots
```

### 3. Configure Laravel

Update `.env`:

```env
REDIS_CLUSTER_ENABLED=true
REDIS_CLUSTER_URL=redis://:SuperSecretPass123!@redis-cluster-0.redis-cluster.catvrf-prod.svc.cluster.local:6379,redis-cluster-1.redis-cluster.catvrf-prod.svc.cluster.local:6379,redis-cluster-2.redis-cluster.catvrf-prod.svc.cluster.local:6379,redis-cluster-3.redis-cluster.catvrf-prod.svc.cluster.local:6379,redis-cluster-4.redis-cluster.catvrf-prod.svc.cluster.local:6379,redis-cluster-5.redis-cluster.catvrf-prod.svc.cluster.local:6379
REDIS_PASSWORD=SuperSecretPass123!
REDIS_CLIENT=phpredis
REVERB_SCALING_ENABLED=true
```

Clear caches:

```bash
php artisan cache:clear
php artisan config:clear
```

---

## Manual Deployment Steps

If you prefer manual deployment:

```bash
# 1. Create namespace
kubectl apply -f namespace.yaml

# 2. Create secret
kubectl create secret generic redis-cluster-secret \
  --namespace=catvrf-prod \
  --from-literal=redis-password=SuperSecretPass123!

# 3. Create configmap
kubectl apply -f configmap.yaml

# 4. Create storageclass (if needed)
kubectl apply -f storageclass.yaml

# 5. Create headless service
kubectl apply -f service.yaml

# 6. Deploy statefulset
kubectl apply -f statefulset.yaml

# 7. Wait for pods to be ready
kubectl wait --for=condition=ready pod -l app=redis-cluster -n catvrf-prod --timeout=600s

# 8. Initialize cluster
kubectl apply -f init-job.yaml

# 9. Wait for init job
kubectl wait --for=condition=complete job/redis-cluster-init -n catvrf-prod --timeout=300s

# 10. Deploy exporter
kubectl apply -f exporter.yaml
kubectl apply -f exporter-service.yaml

# 11. Deploy HPA
kubectl apply -f hpa.yaml

# 12. Create PDB
kubectl apply -f poddisruptionbudget.yaml
```

---

## Scaling

### Adding Nodes

**Increase StatefulSet replicas:**

```bash
# Scale to 9 nodes (6 master + 3 replica)
kubectl scale statefulset redis-cluster -n catvrf-prod --replicas=9

# Wait for new pods to be ready
kubectl wait --for=condition=ready pod -l app=redis-cluster -n catvrf-prod --timeout=600s

# Add new nodes to cluster
kubectl exec -n catvrf-prod redis-cluster-0 -- redis-cli --cluster add-node \
  redis-cluster-6.redis-cluster.catvrf-prod.svc.cluster.local:6379 \
  redis-cluster-0.redis-cluster.catvrf-prod.svc.cluster.local:6379 \
  -a SuperSecretPass123!

# Reshard slots
kubectl exec -n catvrf-prod redis-cluster-0 -- redis-cli --cluster reshard \
  redis-cluster-6.redis-cluster.catvrf-prod.svc.cluster.local:6379 \
  -a SuperSecretPass123!
```

### Vertical Scaling

**Edit StatefulSet resources:**

```bash
kubectl edit statefulset redis-cluster -n catvrf-prod

# Update resources:
resources:
  requests:
    cpu: "1000m"  # Increase from 500m
    memory: "8Gi"  # Increase from 5Gi
  limits:
    cpu: "2000m"  # Increase from 1500m
    memory: "10Gi" # Increase from 6Gi
```

---

## Monitoring

### Prometheus Metrics

Access metrics:

```bash
# Port forward exporter
kubectl port-forward -n catvrf-prod svc/redis-exporter 9121:9121

# Access metrics
curl http://localhost:9121/metrics
```

### Key Metrics

- `redis_up` - Instance availability
- `redis_cluster_enabled` - Cluster mode status
- `redis_cluster_slots_ok` - Slots in OK state
- `redis_connected_clients` - Active connections
- `redis_commands_processed_total` - Total commands
- `redis_keyspace_hits` / `redis_keyspace_misses` - Cache hit ratio
- `redis_evicted_keys` - Evicted keys

### Grafana Dashboard

Import the Prometheus rules:

```bash
kubectl apply -f prometheus-rules.yaml
```

Alerts include:
- Cluster Down
- High Memory Usage
- Low Cache Hit Ratio
- High Connection Count
- Cluster Slot Coverage
- Evicted Keys
- Node Failure

---

## Operations

### Failover Testing

```bash
# Simulate master failure
kubectl delete pod redis-cluster-0 -n catvrf-prod

# Watch automatic failover
kubectl exec -n catvrf-prod redis-cluster-1 -- redis-cli -a SuperSecretPass123! cluster nodes

# Restart pod
kubectl wait --for=condition=ready pod -l statefulset.kubernetes.io/pod-name=redis-cluster-0 -n catvrf-prod
```

### Backup

```bash
# Backup AOF from each node
for i in {0..5}; do
  kubectl exec -n catvrf-prod redis-cluster-$i -- cat /data/appendonly.aof > backup/redis-$i-$(date +%Y%m%d).aof
done
```

### Restore

```bash
# Stop cluster
kubectl scale statefulset redis-cluster -n catvrf-prod --replicas=0

# Restore AOF files
for i in {0..5}; do
  kubectl cp backup/redis-$i-YYYYMMDD.aof catvrf-prod/redis-cluster-$i:/data/appendonly.aof
done

# Start cluster
kubectl scale statefulset redis-cluster -n catvrf-prod --replicas=6
```

### Logs

```bash
# View all pod logs
kubectl logs -n catvrf-prod -l app=redis-cluster --tail=100 -f

# View specific pod logs
kubectl logs -n catvrf-prod redis-cluster-0 --tail=100 -f
```

### Exec into Pod

```bash
# Exec into redis-cli
kubectl exec -n catvrf-prod redis-cluster-0 -it -- redis-cli -a SuperSecretPass123!

# Monitor commands
kubectl exec -n catvrf-prod redis-cluster-0 -- redis-cli -a SuperSecretPass123! MONITOR
```

---

## Troubleshooting

### Pods Not Starting

**Problem:** Pods stuck in Pending state

**Solution:**
```bash
# Check pod events
kubectl describe pod redis-cluster-0 -n catvrf-prod

# Check StorageClass
kubectl get storageclass

# Check PVC status
kubectl get pvc -n catvrf-prod
```

### Cluster Initialization Failed

**Problem:** Init job failed

**Solution:**
```bash
# Check job logs
kubectl logs job/redis-cluster-init -n catvrf-prod

# Manually initialize
kubectl exec -n catvrf-prod redis-cluster-0 -- redis-cli --cluster create \
  redis-cluster-0.redis-cluster.catvrf-prod.svc.cluster.local:6379 \
  redis-cluster-1.redis-cluster.catvrf-prod.svc.cluster.local:6379 \
  redis-cluster-2.redis-cluster.catvrf-prod.svc.cluster.local:6379 \
  redis-cluster-3.redis-cluster.catvrf-prod.svc.cluster.local:6379 \
  redis-cluster-4.redis-cluster.catvrf-prod.svc.cluster.local:6379 \
  redis-cluster-5.redis-cluster.catvrf-prod.svc.cluster.local:6379 \
  --cluster-replicas 1 -a SuperSecretPass123! --cluster-yes
```

### Connection Issues

**Problem:** Laravel cannot connect

**Solution:**
```bash
# Check service DNS
kubectl exec -n catvrf-prod redis-cluster-0 -- nslookup redis-cluster

# Test connection from pod
kubectl exec -n catvrf-prod redis-cluster-0 -- redis-cli -h redis-cluster-1 -p 6379 -a SuperSecretPass123! ping

# Check firewall rules
# Ensure ports 6379 and 16379 are open
```

### High Memory Usage

**Problem:** Pods using too much memory

**Solution:**
```bash
# Check memory usage
kubectl top pods -n catvrf-prod -l app=redis-cluster

# Check largest keys
kubectl exec -n catvrf-prod redis-cluster-0 -- redis-cli -a SuperSecretPass123! --bigkeys

# Adjust maxmemory in configmap
kubectl edit configmap redis-cluster-config -n catvrf-prod

# Restart pods
kubectl rollout restart statefulset redis-cluster -n catvrf-prod
```

---

## Security

### Password Management

**Use external secret management:**

```bash
# AWS Secrets Manager
kubectl create secret generic redis-cluster-secret \
  --from-env-file <(aws secretsmanager get-secret-value --secret-id catvrf/redis-password --query SecretString --output text)

# Azure Key Vault
kubectl create secret generic redis-cluster-secret \
  --from-literal=redis-password=$(az keyvault secret show --vault-name catvrf-kv --name redis-password --query value -o tsv)

# HashiCorp Vault
kubectl create secret generic redis-cluster-secret \
  --from-literal=redis-password=$(vault kv get -field=value catvrf/redis-password)
```

### Network Policies

**Restrict access to Redis:**

```yaml
apiVersion: networking.k8s.io/v1
kind: NetworkPolicy
metadata:
  name: redis-cluster-netpol
  namespace: catvrf-prod
spec:
  podSelector:
    matchLabels:
      app: redis-cluster
  policyTypes:
  - Ingress
  - Egress
  ingress:
  - from:
    - namespaceSelector:
        matchLabels:
          name: catvrf-app
    ports:
    - protocol: TCP
      port: 6379
```

---

## Performance Tuning

### Resource Limits

**For high-throughput (28 verticals):**

```yaml
resources:
  requests:
    cpu: "1000m"
    memory: "8Gi"
  limits:
    cpu: "3000m"
    memory: "12Gi"
```

### Storage Performance

**Use local SSD for maximum performance:**

```yaml
storageClassName: local-ssd
resources:
  requests:
    storage: 100Gi
```

### Network Optimization

**Enable host network for low latency:**

```yaml
spec:
  hostNetwork: true
  dnsPolicy: ClusterFirstWithHostNet
```

---

## Disaster Recovery

### Backup Strategy

1. **Regular AOF backups** (hourly)
2. **Snapshot persistent volumes** (daily)
3. **Cross-region replication** (weekly)

### Restore Procedure

1. Create new namespace
2. Restore PVCs from snapshots
3. Deploy StatefulSet
4. Initialize cluster
5. Verify data integrity

---

## Cost Optimization

### Spot Instances

**Use spot instances for replicas:**

```yaml
nodeSelector:
  cloud.google.com/gke-spot: "true"
```

### Auto-scaling

**Use Cluster Autoscaler:**

```yaml
autoscaling:
  enabled: true
  minReplicas: 6
  maxReplicas: 12
```

---

## References

- [Redis Cluster Specification](https://redis.io/docs/manual/scaling/)
- [Kubernetes StatefulSets](https://kubernetes.io/docs/concepts/workloads/controllers/statefulset/)
- [Redis Exporter](https://github.com/oliver006/redis_exporter)
- [Prometheus Operator](https://prometheus-operator.dev/)

---

## Support

For issues or questions:
- **Architecture:** Sensei (ex-Amazon, Alibaba, Ozon)
- **Documentation:** docs/REDIS_CLUSTER_KUBERNETES.md
- **Scripts:** k8s/redis-cluster/deploy.sh

---

**Last Updated:** 2026-04-26  
**Status:** Production Ready ✅
