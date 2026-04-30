# Windows Development & Production Octane Deployment Guide

## Problem: Octane/Swoole on Windows

**Octane servers (Swoole, FrankenPHP, RoadRunner) do not work natively on Windows** due to:
- Missing Unix signals (SIGINT, SIGTERM, SIGHUP)
- Swoole extension not available for Windows
- Platform-specific binary requirements

## Solution: Hybrid Approach

### Development (Windows)
Use standard Laravel development server:
```bash
php artisan serve
```

### Production (WSL/Linux)
Deploy Octane with Swoole for maximum performance (3-10x speedup).

---

## Local Development on Windows

### Start Development Server
```bash
php artisan serve
```

Access: `http://localhost:8000`

### Run Queue Workers
```bash
php artisan queue:work
```

### Run Scheduler
```bash
php artisan schedule:work
```

---

## Production Deployment (WSL/Linux + Octane)

### WSL/Linux Setup
Install Swoole and run Octane:

```bash
# Inside WSL2 Ubuntu:
sudo apt update
sudo apt install php8.3 php8.3-dev php-pear
sudo pecl install openswoole
php artisan octane:start --server=swoole --host=0.0.0.0 --port=8000
```

---

## Octane Configuration (Already Done)

All Octane infrastructure is already configured:

### Config Files
- `config/octane.php` - Octane server configuration
- `config/broadcasting.php` - Swoole broadcasting driver

### Services Created
- `app/Octane/Services/SwooleTableService.php` - Hot data management
- `app/Octane/Services/SwooleCoroutineService.php` - Coroutine-safe async execution
- `app/Octane/Services/SwooleWebSocketService.php` - WebSocket server
- `app/Octane/Services/PrometheusSwooleExporter.php` - Metrics export

### Listeners
- `app/Octane/Listeners/InitializeSwooleTables.php`
- `app/Octane/Listeners/WarmupSwooleCache.php`
- `app/Octane/Listeners/FlushSwooleTables.php`
- `app/Octane/Listeners/LogWorkerError.php`

### Middleware
- `app/Http/Middleware/OctaneGracefulShutdown.php`
- `app/Http/Middleware/OctaneRequestCleanup.php`

### Example Services
- `app/Domains/Auto/Services/AIDiagnosticsServiceCoroutine.php` - Parallel AI calls
- `app/Services/Payment/PaymentServiceCoroutine.php` - Parallel payment gateways

---

## Swoole Tables (Hot Data)

6 tables configured for in-memory hot data:

1. **slot_holds** - Appointment slot reservations (10,000 rows)
2. **quota_counters** - API rate limiting (10,000 rows)
3. **video_rooms** - Video consultation rooms (5,000 rows)
4. **rate_limits** - Per-user rate limits (50,000 rows)
5. **session_cache** - Session data (20,000 rows)
6. **fraud_cache** - Fraud detection cache (10,000 rows)

All tables auto-initialize on Octane worker start and persist to Redis on shutdown.

---

## Performance Expectations

### Without Octane (Standard PHP-FPM)
- ~500-1000 RPS per instance
- 50-100ms latency per request
- Cold start on every request

### With Octane + Swoole (Production)
- **5,000-50,000+ RPS** per instance
- **10-30ms latency** per request
- Warm workers, no cold start
- Parallel AI calls (2-3x faster)
- Parallel payment gateway calls (2-5x faster)

---

## Testing Octane Locally (WSL2)

If you want to test Octane on Windows:

### Option 1: WSL2
```bash
# Install WSL2 on Windows
wsl --install

# Inside WSL2 Ubuntu:
sudo apt update
sudo apt install php8.3 php8.3-dev php-pear
sudo pecl install openswoole
php artisan octane:start --server=swoole
```

### Option 2: Native Linux
```bash
sudo apt install php8.3 php8.3-dev php-pear
sudo pecl install openswoole
php artisan octane:start --server=swoole
```

---

## Code Compatibility

**All CatVRF code is Octane-ready:**
- No static state in services
- Proper dependency injection
- Memory leak prevention patterns
- Coroutine-safe examples provided

The same code works on:
- Windows development (php artisan serve)
- Linux production (Octane + Swoole)

---

## Migration Path

1. **Development (Windows)**: Use `php artisan serve`
2. **Staging (WSL/Linux)**: Test Octane configuration
3. **Production (Linux)**: Deploy with Octane + Swoole

No code changes required between environments.

---

## Environment Variables

### Development (.env)
```env
OCTANE_SERVER=  # Leave empty for standard PHP
```

### Production (.env)
```env
OCTANE_SERVER=swoole
SWOOLE_HTTP_WORKER_COUNT=4
SWOOLE_TASK_WORKER_COUNT=4
FRANKENPHP_HOST=0.0.0.0
FRANKENPHP_PORT=8000
```

---

## Monitoring

### Octane Health Endpoint
```
GET /api/octane/health
```

Response:
```json
{
  "status": "healthy",
  "server": "swoole",
  "workers": {
    "http": 4,
    "task": 4
  },
  "coroutines_enabled": true
}
```

### Prometheus Metrics
```
GET /api/octane/metrics
```

Exports Swoole metrics in Prometheus format.

---

## Troubleshooting

### Windows Development
**Q**: Octane commands fail on Windows
**A**: Expected. Use `php artisan serve` for development.

### Production Deployment
**Q**: Swoole extension not found
**A**: Install in Dockerfile:
```dockerfile
RUN pecl install openswoole && docker-php-ext-enable openswoole
```

**Q**: Workers not starting
**A**: Check logs: `storage/logs/swoole.log`

---

## Summary

- **Windows Development**: Standard Laravel (`php artisan serve`)
- **Production**: Octane + Swoole (3-10x performance)
- **Code**: Octane-ready, no changes needed
- **Infrastructure**: Fully configured and documented

All Octane integration work is complete. Simply deploy to Docker/Linux for production performance.
