# WebSocket Scaling for CatVRF — 28 Verticals

**Version:** 2026.1  
**Status:** Production-Ready  
**Architecture:** Laravel Reverb + Redis Pub/Sub + Horizontal Scaling

---

## 📋 Executive Summary

CatVRF uses **Laravel Reverb** (native Laravel 11+ WebSocket server) with horizontal scaling to support **30k-50k+ concurrent connections** across 28 verticals. The architecture uses:

- **Laravel Reverb** — Native WebSocket server for Laravel 11+
- **Redis Pub/Sub** — Channel synchronization across multiple Reverb instances
- **Channel Sharding** — Vertical-prefixed channels (`v_taxi:order.123`) for Redis optimization
- **Sticky Sessions** — Load balancer affinity to keep clients on same Reverb instance
- **Rate Limiting** — Per-vertical and per-channel rate limiting to prevent spam
- **Priority-based Resource Allocation** — Critical verticals get more resources

---

## 🏗️ Architecture Overview

```
                    ┌─────────────────┐
                    │   Load Balancer │ (NGINX with sticky sessions)
                    │   :8080         │
                    └────────┬────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
    ┌────▼────┐        ┌────▼────┐        ┌────▼────┐
    │ Reverb 1│        │ Reverb 2│        │ Reverb 3│  (4 instances)
    │ :6001   │        │ :6002   │        │ :6003   │  (10k conn each)
    └────┬────┘        └────┬────┘        └────┬────┘
         │                   │                   │
         └───────────────────┼───────────────────┘
                             │
                    ┌────────▼────────┐
                    │   Redis Pub/Sub │  (Channel synchronization)
                    │   :6379         │
                    └─────────────────┘
```

### Key Components

1. **Laravel Reverb Servers** (4 instances)
   - Each handles up to 10k concurrent connections
   - Total capacity: 40k concurrent connections
   - Auto-scaling possible via Kubernetes

2. **Redis Pub/Sub**
   - Synchronizes channel state across all Reverb instances
   - Enables horizontal scaling without state loss
   - Recommended: Redis Cluster for HA

3. **NGINX Load Balancer**
   - Distributes WebSocket connections
   - Sticky sessions (cookie-based) keep clients on same instance
   - Health checks for Reverb instances

4. **RealtimeScalingService**
   - Channel prefixing by vertical (`v_{vertical}:{channel}`)
   - Dynamic rate limiting per vertical
   - Connection statistics per vertical
   - Priority-based resource allocation

---

## 🚀 Deployment

### Quick Start

```bash
# Copy environment variables
cp .env.reverb.example .env

# Edit .env with your values
nano .env

# Start Reverb server
php artisan reverb:start --host=0.0.0.0 --port=8080 &

# Check status
ps aux | grep reverb
```

### Production Deployment (Kubernetes)

```yaml
# deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: reverb
spec:
  replicas: 4
  selector:
    matchLabels:
      app: reverb
  template:
    spec:
      containers:
      - name: reverb
        image: catvrf-reverb:latest
        ports:
        - containerPort: 6001
        env:
        - name: REVERB_SCALING_ENABLED
          value: "true"
        - name: REVERB_MAX_CONNECTIONS
          value: "10000"
        resources:
          requests:
            memory: "512Mi"
            cpu: "500m"
          limits:
            memory: "2Gi"
            cpu: "2000m"
```

---

## 🔧 Configuration

### 1. Broadcasting Configuration (`config/broadcasting.php`)

```php
return [
    'default' => env('BROADCAST_DRIVER', 'reverb'),
    
    'connections' => [
        'reverb' => [
            'driver' => 'reverb',
            'host' => env('REVERB_HOST', '0.0.0.0'),
            'port' => env('REVERB_PORT', 6001),
            'app_id' => env('REVERB_APP_ID', 'laravel'),
            'app_key' => env('REVERB_APP_KEY'),
            'app_secret' => env('REVERB_APP_SECRET'),
            
            // Horizontal scaling via Redis
            'scaling' => [
                'enabled' => env('REVERB_SCALING_ENABLED', false),
                'redis' => [
                    'connection' => env('REVERB_REDIS_CONNECTION', 'default'),
                    'prefix' => env('REVERB_REDIS_PREFIX', 'reverb'),
                ],
            ],
            
            // Connection limits
            'max_connections' => env('REVERB_MAX_CONNECTIONS', 10000),
            
            // Rate limiting
            'rate_limiting' => [
                'enabled' => env('REVERB_RATE_LIMITING_ENABLED', true),
                'max_messages_per_second' => env('REVERB_MAX_MESSAGES_PER_SECOND', 1000),
                'max_messages_per_minute_per_channel' => env('REVERB_MAX_MESSAGES_PER_MINUTE_PER_CHANNEL', 120),
            ],
        ],
    ],
    
    // Vertical scaling configuration
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
];
```

### 2. Vertical Configuration (`config/verticals.php`)

Each vertical has its own realtime configuration:

```php
'taxi' => [
    'domain' => 'Taxi',
    'model' => 'DeliveryOrder',
    'queue' => 2,
    'active' => true,
    'realtime' => [
        'enabled' => true,
        'update_interval' => 2,           // seconds
        'priority' => 'critical',          // critical, high, medium, low
        'max_connections_per_entity' => 6, // buyer + driver + support + etc
        'rate_limit' => [
            'max_messages_per_minute' => 120,
        ],
        'show_driver_location' => true,
        'use_presence_channels' => true,
    ],
],
```

### 3. Environment Variables (`.env`)

```bash
BROADCAST_DRIVER=reverb

# Reverb configuration
REVERB_APP_ID=laravel
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=0.0.0.0
REVERB_PORT=6001

# Scaling
REVERB_SCALING_ENABLED=true
REVERB_REDIS_CONNECTION=default
REVERB_MAX_CONNECTIONS=10000

# Rate limiting
REVERB_RATE_LIMITING_ENABLED=true
REVERB_MAX_MESSAGES_PER_SECOND=1000
REVERB_MAX_MESSAGES_PER_MINUTE_PER_CHANNEL=120

# Vertical scaling
BROADCAST_CHANNEL_SHARDING_ENABLED=true
BROADCAST_STICKY_SESSIONS_ENABLED=true
BROADCAST_MONITORING_ENABLED=true
```

---

## 📊 Vertical Priorities

### Critical Priority (2s update interval, 120 msg/min)
- `taxi` — Real-time driver tracking
- `pharmacy` — Urgent delivery tracking
- `delivery` — Courier location updates
- `fraud_ml` — Real-time fraud detection
- `security` — Security event broadcasting
- `geo_logistics` — Logistics route updates
- `payment` — Payment status updates
- `realtime` — Core realtime infrastructure

### High Priority (5-10s update interval, 60 msg/min)
- `confectionery` — Cold chain monitoring
- `flowers` — Perishable handling
- `food` — Cold chain monitoring
- `grocery_and_delivery` — Cold chain + ETA
- `meat_shops` — Cold chain monitoring
- `communication` — Real-time messaging
- `geo` — Location updates
- `marketplace` — Listing updates
- `notifications` — Push notifications
- `cart` — Cart updates
- `search` — Search results
- `wallet` — Wallet balance updates

### Medium Priority (10-30s update interval, 20-30 msg/min)
- `auto` — Vehicle tracking
- `car_rental` — Delivery tracking
- `hotels` — Check-in status
- `furniture` — Courier location
- `a_i` — AI processing updates
- `analytics` — Analytics events
- `finances` — Finance updates
- `inventory` — Inventory changes
- `c_r_m` — CRM updates
- `bonuses` — Bonus transactions
- `commissions` — Commission updates
- `payout` — Payout status
- `audit` — Audit logs
- `user_profile` — Profile updates
- `compliance` — Compliance events
- `staff` — Staff updates
- `b2b` — B2B updates

### Low Priority (30-60s update interval, 10 msg/min)
- `beauty` — Appointment updates
- `cleaning_services` — Order updates
- `fitness` — Membership updates
- `home_services` — Order updates
- `medical` — Appointment updates
- `real_estate` — Property viewing
- `short_term_rentals` — Booking updates
- `advertising` — Ad campaign updates
- `referral` — Referral updates
- `h_r` — HR updates

### Disabled (Batch Processing / Infrastructure Only)
- `common` — Infrastructure only
- `demand_forecast` — Batch processing
- `recommendation` — Batch processing
- `m_l` — Batch processing
- `big_data` — Batch processing
- `webhooks` — HTTP-based

---

## 💻 Usage

### Starting a Tracking Session

```php
use App\Domains\Shared\Realtime\RealtimeTrackingAdapter;

class TaxiService
{
    public function __construct(
        private readonly RealtimeTrackingAdapter $trackingAdapter,
    ) {}

    public function startRideTracking(int $rideId, int $driverId, int $passengerId): array
    {
        return $this->trackingAdapter->startTracking([
            'order_id' => $rideId,
            'vertical' => 'taxi',
            'courier_id' => $driverId,
            'buyer_id' => $passengerId,
        ]);
    }
}
```

### Broadcasting Location Updates

```php
// Courier device sends location
$this->trackingAdapter->broadcastLocationUpdate(
    orderId: $rideId,
    vertical: 'taxi',
    coords: [
        'lat' => 55.7558,
        'lon' => 37.6173,
        'heading' => 45,
        'speed' => 30,
    ],
    correlationId: $correlationId
);
```

### Client-Side (JavaScript)

```javascript
// Connect to Reverb via load balancer
const echo = new Echo({
    broadcaster: 'reverb',
    host: 'https://your-domain.com',
    key: 'your-app-key',
    wsHost: 'ws.your-domain.com',
    wsPort: 8080,
    forceTLS: true,
});

// Listen for location updates
echo.private(`v_taxi:order.${orderId}.tracking`)
    .listen('.location.updated', (e) => {
        console.log('Driver location:', e);
        updateMap(e.lat, e.lon);
    });

// Listen for ETA updates
echo.private(`v_taxi:order.${orderId}.tracking`)
    .listen('.eta.updated', (e) => {
        console.log('ETA:', e.eta_minutes);
        updateETA(e.eta_minutes);
    });
```

---

## 📈 Monitoring

### Key Metrics

- **Concurrent Connections** — Per Reverb instance
- **Messages/Second** — Global broadcast rate
- **Channel Subscriptions** — Per vertical
- **Redis Pub/Sub Latency** — Should be < 8ms
- **Message Delivery Rate** — Success/failure ratio
- **Rate Limit Violations** — Per vertical

### Prometheus Queries

```promql
# Concurrent connections per instance
broadcast_connections_total{instance="reverb-1"}

# Messages per second
rate(broadcast_messages_total[1m])

# Redis latency
rate(redis_pubsub_latency_seconds[5m])

# Vertical-specific stats
broadcast_vertical_messages_total{vertical="taxi"}
```

### Grafana Dashboard

Create a dashboard with:
1. **Connections Panel** — Line chart of concurrent connections
2. **Messages Panel** — Bar chart of messages/sec per vertical
3. **Latency Panel** — Gauge of Redis pub/sub latency
4. **Rate Limit Panel** — Counter of rate limit violations
5. **Health Panel** — Status of each Reverb instance

---

## 🔒 Security

### Authentication

```php
// Broadcast authentication in routes/channels.php
Broadcast::channel('v_{vertical}.order.{orderId}', function ($user, $vertical, $orderId) {
    // Check if user has access to this order
    return $user->can('view', Order::findOrFail($orderId));
});
```

### Rate Limiting

- Global: 1000 messages/second per Reverb instance
- Per channel: 120 messages/minute
- Per vertical: Configurable in `config/verticals.php`

### TLS/SSL

```nginx
# nginx configuration for HTTPS
server {
    listen 443 ssl http2;
    server_name ws.your-domain.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    location / {
        proxy_pass http://reverb_backend;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        # ... other WebSocket settings
    }
}
```

---

## 🛠️ Troubleshooting

### Connections Dropping

**Symptom:** Clients disconnect frequently

**Solutions:**
1. Check NGINX timeout settings (should be 7d for WebSocket)
2. Verify sticky sessions are working
3. Check Reverb instance health
4. Review Redis connection stability

### High Redis Latency

**Symptom:** Messages delayed > 100ms

**Solutions:**
1. Use Redis Cluster instead of single instance
2. Enable Redis persistence (AOF)
3. Monitor Redis memory usage
4. Consider separate Redis for pub/sub

### Rate Limit Violations

**Symptom:** Messages not reaching clients

**Solutions:**
1. Check vertical rate limit configuration
2. Implement exponential backoff on client
3. Use presence channels only where necessary
4. Optimize update intervals

### Channel Not Found

**Symptom:** 404 when subscribing to channel

**Solutions:**
1. Verify channel prefix format (`v_{vertical}:{channel}`)
2. Check vertical is active in `config/verticals.php`
3. Ensure realtime is enabled for the vertical
4. Review broadcast authentication rules

---

## 🚦 Scaling Strategy

### Vertical Scaling (Single Instance)

- Increase `REVERB_MAX_CONNECTIONS`
- Add more CPU/RAM to Reverb instances
- Optimize Redis configuration

### Horizontal Scaling (Multiple Instances)

- Add more Reverb instances (multiple processes)
- Use Kubernetes HPA for auto-scaling
- Configure load balancer with sticky sessions
- Use Redis Cluster for HA

### Database Scaling

- Separate Redis for pub/sub and cache
- Use Redis Sentinel for failover
- Consider separate database per vertical (if needed)

---

## 📝 Best Practices

1. **Use Private Channels** — For user-specific data
2. **Presence Channels Sparingly** — Only for Taxi, Events
3. **Channel Prefixing** — Always use `v_{vertical}:{channel}`
4. **Rate Limiting** — Respect per-vertical limits
5. **Fallback** - Implement polling fallback for critical verticals
6. **Monitoring** — Track metrics per vertical
7. **Testing** — Load test before production deployment
8. **Security** — Always authenticate channel subscriptions

---

## 🔗 References

- [Laravel Reverb Documentation](https://laravel.com/docs/reverb)
- [Redis Pub/Sub](https://redis.io/docs/manual/pubsub/)
- [WebSocket Protocol](https://tools.ietf.org/html/rfc6455)
- [NGINX WebSocket Proxying](https://nginx.org/en/docs/http/websocket.html)

---

**Last Updated:** 2026-04-26  
**Maintained By:** CatVRF Infrastructure Team
