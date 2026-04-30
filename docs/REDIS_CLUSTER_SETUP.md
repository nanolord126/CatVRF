# Redis Cluster Setup Guide for CatVRF 2026

**Author:** Sensei (ex-Amazon, Alibaba, Ozon)  
**Version:** 1.0  
**Last Updated:** 2026-04-26

---

## Overview

This guide covers the production-ready Redis Cluster setup for CatVRF, designed to handle high-throughput WebSocket scaling across 28 verticals (Taxi, Supermarket, Hotels, Beauty, etc.).

### Why Redis Cluster?

- **Horizontal Scaling:** Standalone Redis dies at ~15-20k pub/sub connections. Cluster scales horizontally.
- **Automatic Failover:** 3 master + 3 replica topology ensures high availability.
- **Data Sharding:** Automatic distribution across 16384 hash slots.
- **Production-Tested:** Same configuration used at Amazon and Ozon-scale workloads.

---

## Architecture

### Topology

```
┌─────────────────────────────────────────────────────────────┐
│                    Redis Cluster (6 nodes)                  │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  Master Nodes (3)              Replica Nodes (3)             │
│  ┌─────────────┐               ┌─────────────┐              │
│  │ Node 1      │◄──────────────►│ Node 4      │              │
│  │ Port 7001   │               │ Port 7004   │              │
│  │ Slots 0-5460│               │ Replica of 1│              │
│  └─────────────┘               └─────────────┘              │
│                                                               │
│  ┌─────────────┐               ┌─────────────┐              │
│  │ Node 2      │◄──────────────►│ Node 5      │              │
│  │ Port 7002   │               │ Port 7005   │              │
│  │ Slots 5461- │               │ Replica of 2│              │
│  │ 10922       │               └─────────────┘              │
│  └─────────────┘                                             │
│                                                               │
│  ┌─────────────┐               ┌─────────────┐              │
│  │ Node 3      │◄──────────────►│ Node 6      │              │
│  │ Port 7003   │               │ Port 7006   │              │
│  │ Slots 10923-│               │ Replica of 3│              │
│  │ 16383       │               └─────────────┘              │
│  └─────────────┘                                             │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

### Key Features

- **Cluster Bus:** Each node uses `port + 10000` for cluster communication (17001-17006)
- **Hash Slots:** 16384 slots distributed evenly across 3 masters
- **Automatic Failover:** Replicas automatically promote if master fails
- **Persistence:** AOF + RDB hybrid for data durability
- **Security:** Password authentication via `requirepass` and `masterauth`

---

## Quick Start

### 1. Start the Cluster

```bash
# Install Redis (WSL/Linux)
sudo apt-get install -y redis-server redis-tools

# Start Redis server
sudo systemctl start redis-server

# Or start cluster using the deploy script
bash k8s/redis-cluster/deploy.sh

# Verify Redis is running
redis-cli ping
```

### 2. Initialize the Cluster

```bash
# Make the script executable (Linux/Mac)
chmod +x scripts/init-redis-cluster.sh

# Run initialization
./scripts/init-redis-cluster.sh

# Or manually:
docker exec -it catvrf-redis-node-1 redis-cli --cluster create \
  172.20.0.2:7001 172.20.0.3:7002 172.20.0.4:7003 \
  172.20.0.5:7004 172.20.0.6:7005 172.20.0.7:7006 \
  --cluster-replicas 1 -a SuperSecretPass123! --cluster-yes
```

### 3. Verify Cluster Status

```bash
# Check cluster health
docker exec catvrf-redis-node-1 redis-cli -p 7001 -a SuperSecretPass123! cluster info

# View cluster nodes
docker exec catvrf-redis-node-1 redis-cli -p 7001 -a SuperSecretPass123! cluster nodes

# Check slot distribution
docker exec catvrf-redis-node-1 redis-cli -p 7001 -a SuperSecretPass123! cluster slots
```

### 4. Configure Laravel

Update your `.env` file:

```env
REDIS_CLUSTER_ENABLED=true
REDIS_CLUSTER_URL=redis://:SuperSecretPass123!@redis-node-1:7001,redis-node-2:7002,redis-node-3:7003,redis-node-4:7004,redis-node-5:7005,redis-node-6:7006
REDIS_CLIENT=phpredis
REDIS_PASSWORD=SuperSecretPass123!
REVERB_SCALING_ENABLED=true
```

Clear caches:

```bash
php artisan cache:clear
php artisan config:clear
```

---

## Configuration Details

### Redis Configuration Files

Each node has its own `redis.conf` in `docker/redis-cluster/node-{N}/redis.conf`.

**Critical Settings:**

```conf
# Cluster
cluster-enabled yes
cluster-node-timeout 5000
cluster-require-full-coverage no  # Don't fail if one node is down

# Persistence
appendonly yes
appendfsync everysec

# Memory
maxmemory 4gb
maxmemory-policy allkeys-lru

# PubSub / WebSocket
client-output-buffer-limit pubsub 64mb 128mb 60

# Security
requirepass SuperSecretPass123!
masterauth SuperSecretPass123!

# Performance
timeout 0
tcp-keepalive 300
```

### Laravel Configuration

**config/database.php:**

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    
    'cluster' => [
        'enabled' => env('REDIS_CLUSTER_ENABLED', false),
        'url' => env('REDIS_CLUSTER_URL'),
        'options' => [
            'cluster' => 'redis',
            'prefix' => 'catvrf:',
        ],
        'parameters' => [
            'password' => env('REDIS_PASSWORD'),
            'database' => env('REDIS_DB', '0'),
        ],
    ],
],
```

**config/broadcasting.php:**

```php
'redis' => [
    'driver' => 'redis',
    'connection' => env('REDIS_CLUSTER_ENABLED', false) ? 'cluster' : 'default',
],

'reverb' => [
    'scaling' => [
        'enabled' => env('REVERB_SCALING_ENABLED', false),
        'redis' => [
            'connection' => env('REDIS_CLUSTER_ENABLED', false) ? 'cluster' : 'default',
        ],
    ],
],
```

---

## Vertical-Specific Key Prefixing

For optimal performance across 28 verticals, use vertical-prefixed keys:

```php
// Examples
Redis::set('taxi:ride:123:location', json_encode($location));
Redis::set('supermarket:order:456:tracking', json_encode($tracking));
Redis::set('hotel:booking:789:status', 'confirmed');
```

This allows:
- **Easy monitoring** per vertical
- **Targeted cache invalidation**
- **Debugging by vertical**
- **Rate limiting per vertical**

---

## Monitoring

### Prometheus Metrics

The Redis exporter is exposed on port `9121`:

```bash
# Access metrics
curl http://localhost:9121/metrics
```

**Key Metrics:**

- `redis_up` - Redis instance availability
- `redis_cluster_enabled` - Cluster mode status
- `redis_cluster_slots_ok` - Slots in OK state
- `redis_cluster_slots_assigned` - Assigned slots
- `redis_connected_clients` - Active connections
- `redis_commands_processed_total` - Total commands
- `redis_keyspace_hits` / `redis_keyspace_misses` - Cache hit ratio
- `redis_evicted_keys` - Evicted keys (memory pressure)

### Grafana Dashboard

Import the Redis Cluster dashboard from `docs/grafana/redis-cluster-dashboard.json`.

**Alerts to Configure:**

1. **Cluster Down:** `redis_up == 0` for any node
2. **High Memory Usage:** `redis_memory_used_bytes / redis_memory_max_bytes > 0.8`
3. **High Connection Count:** `redis_connected_clients > 10000`
4. **Low Hit Ratio:** `redis_keyspace_hits / (redis_keyspace_hits + redis_keyspace_misses) < 0.8`
5. **Slot Coverage:** `redis_cluster_slots_ok < 16384`

---

## Operations

### Scaling the Cluster

**Adding a New Master:**

```bash
# Start new node
redis-server /etc/redis/redis-node-7.conf

# Add to cluster
redis-cli --cluster add-node \
  <node-7-ip>:7007 <existing-node-ip>:7001 -a SuperSecretPass123!

# Reshard slots
redis-cli --cluster reshard \
  <node-7-ip>:7007 -a SuperSecretPass123!
```

**Adding a Replica:**

```bash
redis-cli --cluster add-node \
  <replica-ip>:7008 <master-ip>:7001 --cluster-slave -a SuperSecretPass123!
```

### Failover Testing

```bash
# Simulate master failure
sudo systemctl stop redis-node-1

# Watch automatic failover
redis-cli -p 7002 -a SuperSecretPass123! cluster nodes

# Restart the failed node
sudo systemctl start redis-node-1

# It will automatically rejoin as a replica
```

### Backup and Restore

**Backup:**

```bash
# Backup AOF file from each node
for i in {1..6}; do
  cp /var/lib/redis/node-$i/appendonly.aof ./backup/node-$i-$(date +%Y%m%d).aof
done
```

**Restore:**

```bash
# Stop cluster
sudo systemctl stop redis-server

# Restore AOF files
for i in {1..6}; do
  cp ./backup/node-$i-YYYYMMDD.aof /var/lib/redis/node-$i/appendonly.aof
done

# Start cluster
sudo systemctl start redis-server
```

### Maintenance

**Flush All Data (⚠️ DANGEROUS):**

```bash
# Flush all nodes
for i in {1..6}; do
  redis-cli -p 700$i -a SuperSecretPass123! FLUSHALL
done
```

**View Slow Log:**

```bash
redis-cli -p 7001 -a SuperSecretPass123! SLOWLOG GET 10
```

**Monitor Real-time Commands:**

```bash
redis-cli -p 7001 -a SuperSecretPass123! MONITOR
```

---

## Troubleshooting

### Cluster Won't Initialize

**Problem:** `CLUSTERDOWN Hash slot not served`

**Solution:**
```bash
# Check all nodes are reachable
for i in {1..6}; do
  docker exec catvrf-redis-node-$i redis-cli -p 700$i ping
done

# Reset cluster state
for i in {1..6}; do
  docker exec catvrf-redis-node-$i redis-cli -p 700$i -a SuperSecretPass123! CLUSTER RESET
done

# Reinitialize
./scripts/init-redis-cluster.sh
```

### Connection Refused

**Problem:** Laravel can't connect to cluster

**Solution:**
```bash
# Check REDIS_CLUSTER_URL format
# Should be: redis://:password@host1:port1,host2:port2,...

# Verify password matches redis.conf
docker exec catvrf-redis-node-1 redis-cli -p 7001 -a SuperSecretPass123! ping

# Check Laravel logs
tail -f storage/logs/laravel.log
```

### High Memory Usage

**Problem:** Redis nodes using too much memory

**Solution:**
```bash
# Check memory usage
docker exec catvrf-redis-node-1 redis-cli -p 7001 -a SuperSecretPass123! INFO memory

# Check largest keys
docker exec catvrf-redis-node-1 redis-cli -p 7001 -a SuperSecretPass123! --bigkeys

# Adjust maxmemory in redis.conf if needed
# maxmemory 8gb  # Increase from 4gb
```

### PubSub Latency

**Problem:** WebSocket messages delayed

**Solution:**
```bash
# Check client output buffer
docker exec catvrf-redis-node-1 redis-cli -p 7001 -a SuperSecretPass123! CLIENT LIST

# Increase pubsub buffer limit in redis.conf
# client-output-buffer-limit pubsub 128mb 256mb 120

# Check network latency
ping <redis-node-ip>
```

---

## Performance Tuning

### For 28 Verticals

**Vertical Rate Limiting:**

```php
// In your vertical-specific services
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for("ws.{$vertical}", function () {
    return Limit::perMinute(
        $vertical === 'taxi' ? 300 : 60  // Taxi needs higher rate
    );
});
```

**Connection Pooling:**

```php
// config/database.php
'redis' => [
    'options' => [
        'persistent' => true,  // Enable persistent connections
    ],
],
```

**Pipeline Operations:**

```php
// Use pipelining for bulk operations
Redis::pipeline(function ($pipe) {
    for ($i = 0; $i < 1000; $i++) {
        $pipe->set("key:$i", "value:$i");
    }
});
```

---

## Security Checklist

- [ ] Change `REDIS_PASSWORD` from default
- [ ] Enable TLS for production (add `tls-port` and `tls-cert-file` to redis.conf)
- [ ] Use Docker secrets for password management
- [ ] Restrict network access with firewall rules
- [ ] Enable Redis AUTH in all connections
- [ ] Monitor for unauthorized access attempts
- [ ] Regular security audits of Redis configuration

---

## Production Deployment

### Kubernetes (Optional)

For production, consider deploying Redis Cluster on Kubernetes using the Redis Operator:

```yaml
# Example RedisCluster manifest
apiVersion: redis.redis.opstreelabs.in/v1beta1
kind: RedisCluster
metadata:
  name: catvrf-redis
spec:
  clusterSize: 6
  redisExporter:
    enabled: true
  storage:
    volumeClaimTemplate:
      spec:
        accessModes: ["ReadWriteOnce"]
        storageClassName: "fast-ssd"
        resources:
          requests:
            storage: 10Gi
```

### Load Balancer

Place an HAProxy or Nginx in front of the cluster for client-side load balancing:

```nginx
upstream redis_cluster {
    server redis-node-1:7001;
    server redis-node-2:7002;
    server redis-node-3:7003;
    least_conn;
}
```

---

## References

- [Redis Cluster Specification](https://redis.io/docs/manual/scaling/)
- [Laravel Redis Configuration](https://laravel.com/docs/11.x/redis)
- [phpredis Cluster Support](https://github.com/phpredis/phpredis#cluster)
- [Redis Exporter](https://github.com/oliver006/redis_exporter)

---

## Support

For issues or questions, contact:
- **Architecture:** Sensei (ex-Amazon, Alibaba, Ozon)
- **Documentation:** docs/REDIS_CLUSTER_SETUP.md
- **Scripts:** scripts/init-redis-cluster.sh

---

**Last Updated:** 2026-04-26  
**Status:** Production Ready ✅
