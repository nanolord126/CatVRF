# WebSocket Scaling for 28 Verticals - Implementation Guide

**Version:** 2026.1  
**Status:** ✅ Implemented  
**Date:** 2026-04-26

---

## 📋 Overview

This document describes the implementation of horizontal WebSocket scaling for CatVRF's 28 verticals using Laravel Reverb with Redis adapter, channel sharding, and per-vertical rate limiting.

**Architecture Choice:** Laravel Reverb (native Laravel 11+ WebSocket solution)  
**Scaling Strategy:** Horizontal with Redis Pub/Sub + Sticky Sessions  
**Target Capacity:** 30k-50k concurrent connections across 4-8 Reverb instances

---

## 🏗️ Architecture

### Current Setup
- **WebSocket Server:** Laravel Reverb (native Laravel 11+)
- **Pub/Sub Backend:** Redis (configurable for Redis Cluster)
- **Multi-tenancy:** Stancl/tenancy with tenant_id in channels
- **Load Balancer:** Nginx/Traefik with sticky sessions

### Scaling Strategy

```
                    Load Balancer (Sticky Sessions)
                              |
        +---------------------+---------------------+
        |                     |                     |
    Reverb-1              Reverb-2              Reverb-N
   (10k conn)            (10k conn)            (10k conn)
        |                     |                     |
        +---------------------+---------------------+
                              |
                         Redis Cluster
                    (Channel State / Pub/Sub)
```

**Key Features:**
1. **Channel Sharding:** Vertical-prefixed channels (`v_taxi:order.123.tracking`)
2. **Rate Limiting:** Per-vertical and per-entity limits
3. **Priority-Based:** Critical verticals (taxi, pharmacy) get higher limits
4. **Monitoring:** Per-vertical metrics for Prometheus/Grafana
5. **Fallback:** Polling for low-priority verticals if WebSocket fails

---

## 📁 Implementation Files

### 1. RealtimeScalingService
**File:** `app/Domains/Shared/Realtime/Services/RealtimeScalingService.php`

**Responsibilities:**
- Channel prefixing by vertical (`v_{vertical}:{channel}`)
- Dynamic rate limiting per vertical/entity
- Connection statistics tracking (connects, disconnects, messages)
- Vertical configuration retrieval

**Key Methods:**
```php
getShardedChannel(string $vertical, string $channel): string
checkRateLimit(string $vertical, string $entityType, int|string $entityId): bool
recordStats(string $vertical, string $action): void
getStats(string $vertical): array
getVerticalConfig(string $vertical): array
isRealtimeEnabled(string $vertical): bool
getUpdateInterval(string $vertical): int
getPriority(string $vertical): string
getMaxConnectionsPerEntity(string $vertical): int
```

---

### 2. RealtimeTrackingAdapter (Updated)
**File:** `app/Domains/Shared/Realtime/RealtimeTrackingAdapter.php`

**Changes:**
- Injected `RealtimeScalingService`
- Updated all channel methods to use sharded channels
- Added rate limiting checks before broadcasts
- Added statistics recording for all events
- Added vertical-specific configuration checks

**Updated Methods:**
```php
startTracking() - Now checks if realtime enabled, returns update_interval & priority
broadcastLocationUpdate() - Rate limited, records stats
broadcastEtaUpdate() - Rate limited, records stats
broadcastCourierArrival() - Records stats
broadcastDeliveryCompleted() - Records stats
stopTracking() - Records disconnect stats
```

---

### 3. config/verticals.php (Updated)
**File:** `config/verticals.php`

**Added `realtime` section for each Queue 2 vertical:**

```php
'realtime' => [
    'enabled' => true,                    // Enable/disable realtime for vertical
    'update_interval' => 5,              // Update interval in seconds
    'priority' => 'high',                // critical|high|medium|low
    'max_connections_per_entity' => 5,   // Max concurrent connections per order
    'rate_limit' => [
        'max_messages_per_minute' => 60,  // Vertical-specific rate limit
    ],
    'use_presence_channels' => false,    // Use presence channels (expensive)
    'show_courier_location' => true,     // Vertical-specific features
    'show_eta' => true,
    'cold_chain_monitoring' => true,     // For cold chain verticals
],
```

**Vertical Configurations:**

| Vertical | Update Interval | Priority | Rate Limit | Use Presence |
|----------|----------------|----------|------------|--------------|
| taxi | 2s | critical | 120/min | ✅ |
| pharmacy | 3s | critical | 120/min | ❌ |
| delivery | 3s | high | 120/min | ❌ |
| grocery_and_delivery | 5s | high | 60/min | ❌ |
| confectionery | 5s | high | 60/min | ❌ |
| flowers | 5s | high | 60/min | ❌ |
| food | 5s | high | 60/min | ❌ |
| meat_shops | 5s | high | 60/min | ❌ |
| auto | 10s | medium | 30/min | ❌ |
| car_rental | 10s | medium | 30/min | ❌ |
| furniture | 10s | medium | 30/min | ❌ |
| beauty | 30s | low | 10/min | ❌ |
| cleaning_services | 30s | low | 10/min | ❌ |
| fitness | 30s | low | 10/min | ❌ |
| home_services | 30s | low | 10/min | ❌ |
| hotels | 30s | low | 10/min | ❌ |
| medical | 30s | low | 10/min | ❌ |
| real_estate | 30s | low | 10/min | ❌ |
| short_term_rentals | 30s | low | 10/min | ❌ |
| fashion | disabled | - | - | - |
| construction_and_repair | disabled | - | - | - |
| promo_campaigns | disabled | - | - | - |

---

### 4. config/broadcasting.php (Updated)
**File:** `config/broadcasting.php`

**Added Sections:**

#### Reverb Scaling Configuration
```php
'reverb' => [
    // ... existing config ...
    'scaling' => [
        'enabled' => env('REVERB_SCALING_ENABLED', false),
        'redis' => [
            'connection' => env('REVERB_REDIS_CONNECTION', 'default'),
            'prefix' => env('REVERB_REDIS_PREFIX', 'reverb'),
        ],
    ],
    'max_connections' => env('REVERB_MAX_CONNECTIONS', 10000),
    'rate_limiting' => [
        'enabled' => env('REVERB_RATE_LIMITING_ENABLED', true),
        'max_messages_per_second' => env('REVERB_MAX_MESSAGES_PER_SECOND', 1000),
        'max_messages_per_minute_per_channel' => env('REVERB_MAX_MESSAGES_PER_MINUTE_PER_CHANNEL', 120),
    ],
],
```

#### Vertical Scaling Configuration
```php
'vertical_scaling' => [
    'channel_sharding' => [
        'enabled' => env('BROADCAST_CHANNEL_SHARDING_ENABLED', true),
        'prefix' => 'v',
    ],
    'presence_channels' => [
        'default_enabled' => false,
        'max_users_per_channel' => 100,
    ],
    'load_balancer' => [
        'sticky_sessions' => [
            'enabled' => env('BROADCAST_STICKY_SESSIONS_ENABLED', true),
            'method' => env('BROADCAST_STICKY_SESSIONS_METHOD', 'cookie'),
            'cookie_name' => env('BROADCAST_STICKY_COOKIE_NAME', 'reverb_server'),
        ],
    ],
    'monitoring' => [
        'enabled' => env('BROADCAST_MONITORING_ENABLED', true),
        'metrics_prefix' => 'broadcast',
        'collect_per_vertical_stats' => true,
    ],
    'fallback' => [
        'enabled' => env('BROADCAST_FALLBACK_ENABLED', true),
        'polling_interval' => env('BROADCAST_FALLBACK_POLLING_INTERVAL', 10000),
        'max_retries' => env('BROADCAST_FALLBACK_MAX_RETRIES', 3),
    ],
],
```

---

### 5. Environment Configuration
**File:** `.env.example.websocket`

Contains all new environment variables with documentation and deployment notes.

---

## 🚀 Deployment Guide

### Step 1: Add Environment Variables

Copy the WebSocket scaling variables to your `.env`:

```bash
# Enable Reverb scaling
REVERB_SCALING_ENABLED=true
REVERB_REDIS_CONNECTION=default
REVERB_REDIS_PREFIX=reverb

# Set connection limits
REVERB_MAX_CONNECTIONS=10000

# Enable rate limiting
REVERB_RATE_LIMITING_ENABLED=true
REVERB_MAX_MESSAGES_PER_SECOND=1000
REVERB_MAX_MESSAGES_PER_MINUTE_PER_CHANNEL=120

# Enable channel sharding
BROADCAST_CHANNEL_SHARDING_ENABLED=true

# Enable sticky sessions
BROADCAST_STICKY_SESSIONS_ENABLED=true
BROADCAST_STICKY_SESSIONS_METHOD=cookie
BROADCAST_STICKY_COOKIE_NAME=reverb_server

# Enable monitoring
BROADCAST_MONITORING_ENABLED=true

# Enable fallback
BROADCAST_FALLBACK_ENABLED=true
BROADCAST_FALLBACK_POLLING_INTERVAL=10000
BROADCAST_FALLBACK_MAX_RETRIES=3
```

### Step 2: Configure Redis for Scaling

Ensure your Redis configuration supports high-throughput pub/sub:

```php
// config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
        // Add these for production
        'read_timeout' => 2.0,
        'persistent' => true,
    ],
],
```

### Step 3: Deploy Multiple Reverb Instances

**Docker Compose Example:**

```yaml
version: '3.8'
services:
  reverb-1:
    image: laravel/reverb:latest
    env_file: .env
    ports:
      - "6001:6001"
    depends_on:
      - redis
  
  reverb-2:
    image: laravel/reverb:latest
    env_file: .env
    ports:
      - "6002:6001"
    depends_on:
      - redis
  
  reverb-3:
    image: laravel/reverb:latest
    env_file: .env
    ports:
      - "6003:6001"
    depends_on:
      - redis
  
  reverb-4:
    image: laravel/reverb:latest
    env_file: .env
    ports:
      - "6004:6001"
    depends_on:
      - redis
  
  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    command: redis-server --maxmemory 2gb --maxmemory-policy allkeys-lru
```

**Nginx Load Balancer Configuration:**

```nginx
upstream reverb_backend {
    ip_hash; # Sticky sessions by IP
    
    server reverb-1:6001;
    server reverb-2:6001;
    server reverb-3:6001;
    server reverb-4:6001;
}

server {
    listen 80;
    
    location / {
        proxy_pass http://reverb_backend;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        
        # Health check
        proxy_next_upstream error timeout invalid_header http_500 http_502 http_503;
    }
}
```

### Step 4: Configure Monitoring

Add Prometheus metrics collection:

```php
// app/Providers/AppServiceProvider.php
use Spatie\Prometheus\Facades\Prometheus;

public function boot(): void
{
    if (config('broadcasting.vertical_scaling.monitoring.enabled')) {
        Prometheus::addGauge('broadcast_concurrent_connections_total')
            ->help('Total concurrent WebSocket connections');
        
        foreach (config('verticals.verticals') as $slug => $config) {
            if (!($config['active'] ?? false)) continue;
            
            Prometheus::addCounter("broadcast_vertical_{$slug}_messages_total")
                ->help("Total messages for {$slug} vertical");
        }
    }
}
```

### Step 5: Test Scaling

```bash
# Test single instance
php artisan reverb:start

# Test with multiple instances
docker-compose up -d reverb-1 reverb-2 reverb-3 reverb-4

# Load test with k6
k6 run k6/websocket-scaling-test.js
```

---

## 📊 Monitoring & Metrics

### Key Prometheus Metrics

| Metric | Description | Alert Threshold |
|--------|-------------|-----------------|
| `broadcast_concurrent_connections_total` | Total concurrent connections | > 80% of max |
| `broadcast_messages_per_second` | Messages per second | > REVERB_MAX_MESSAGES_PER_SECOND |
| `broadcast_vertical_{slug}_messages_total` | Messages per vertical | Vertical-specific |
| `broadcast_vertical_{slug}_connections_total` | Connections per vertical | Vertical-specific |
| `redis_latency_ms` | Redis operation latency | > 20ms |
| `reverb_memory_usage_bytes` | Reverb memory usage | > 80% of container limit |

### Grafana Dashboard Queries

```promql
# Concurrent connections per vertical
sum by (vertical) (broadcast_vertical_connections_total)

# Messages per second rate
rate(broadcast_messages_total[1m])

# Redis latency
histogram_quantile(0.95, redis_latency_ms_bucket)

# Rate limit breaches
increase(broadcast_rate_limit_exceeded_total[5m])
```

---

## 🎯 Performance Targets

| Metric | Target | Notes |
|--------|--------|-------|
| Concurrent Connections | 30k-50k | Across 4-8 instances |
| Messages/Second | 1000 | Global limit |
| Redis Latency | < 8ms | Pub/Sub operations |
| Connection Establishment | < 100ms | Handshake time |
| Message Delivery | < 50ms | End-to-end latency |

---

## 🔒 Security Considerations

1. **Channel Authorization:** Always authorize private/presence channels
2. **Rate Limiting:** Prevent abuse with per-vertical limits
3. **Redis Security:** Use Redis AUTH and TLS in production
4. **Load Balancer:** Use TLS termination at LB level
5. **Tenant Isolation:** Ensure tenant_id is always included in channel auth

---

## 🐛 Troubleshooting

### Issue: High Redis Memory Usage
**Solution:** 
- Monitor channel subscriptions
- Implement channel cleanup for inactive orders
- Use Redis Cluster for horizontal scaling

### Issue: Connection Drops
**Solution:**
- Check sticky session configuration
- Verify load balancer health checks
- Monitor Redis connection pool

### Issue: Rate Limit Breaches
**Solution:**
- Adjust vertical-specific limits in `config/verticals.php`
- Implement exponential backoff on client side
- Monitor which verticals are hitting limits

---

## 📚 References

- [Laravel Reverb Documentation](https://laravel.com/docs/reverb)
- [Redis Pub/Sub](https://redis.io/docs/manual/pubsub/)
- [WebSocket Scaling Best Practices](https://www.nginx.com/blog/websocket-nginx/)
- [CatVRF Architecture Guide](./README.md)

---

## ✅ Implementation Checklist

- [x] Create RealtimeScalingService
- [x] Update RealtimeTrackingAdapter with scaling support
- [x] Add per-vertical realtime configs to config/verticals.php
- [x] Update config/broadcasting.php with scaling options
- [x] Create .env.example.websocket with all variables
- [x] Document deployment procedure
- [ ] Configure Redis Cluster for production
- [ ] Set up Load Balancer with sticky sessions
- [ ] Implement Prometheus metrics collection
- [ ] Create Grafana dashboard
- [ ] Write load tests with k6
- [ ] Implement circuit breaker for Redis failures

---

**Next Steps:**
1. Review and adjust per-vertical rate limits based on actual traffic patterns
2. Implement automatic scaling based on concurrent connections
3. Add circuit breaker pattern for Redis failures
4. Create client-side fallback mechanism
