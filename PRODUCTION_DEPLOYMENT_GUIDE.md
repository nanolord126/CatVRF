# Production Deployment Guide — CANON 2026

**Date:** 17 March 2026  
**Version:** 1.0 (FINAL)  
**Status:** ✅ PRODUCTION-READY

---

## 📋 Pre-Deployment Checklist

### Environment & Infrastructure
- [ ] **Server Specs:** 4+ CPU cores, 16GB+ RAM, 100GB+ SSD
- [ ] **PHP Version:** 8.3+ with extensions: opcache, redis, pdo_sqlite, curl, json
- [ ] **Database:** PostgreSQL 13+ or MySQL 8+ (primary), SQLite for tenant isolation
- [ ] **Cache:** Redis 6.2+ (required for rate limiting, session storage, fraud scoring)
- [ ] **Message Queue:** Redis (jobs), optional: RabbitMQ for critical operations
- [ ] **Reverse Proxy:** Nginx 1.24+ or Apache 2.4+ with SSL/TLS termination

### Code Quality & Compliance
- [ ] **All Tests Passing:** `php artisan test --env=testing`
- [ ] **E2E Tests Passing:** `npm run test:e2e:run`
- [ ] **Linting:** `./vendor/bin/pint --test` (PHP CS Fixer)
- [ ] **Static Analysis:** `./vendor/bin/phpstan analyse` (Level 9)
- [ ] **Security Audit:** `composer audit` (no vulnerabilities)
- [ ] **CANON 2026 Compliance:** UTF-8, CRLF, declare(strict_types=1), final classes, no TODO
- [ ] **Database Migrations:** All migrations tested locally

### Security & Compliance
- [ ] **SSL/TLS Certificate:** Valid, auto-renewal configured
- [ ] **CORS Configuration:** Restricted to trusted domains
- [ ] **CSRF Protection:** Enabled on all forms
- [ ] **Rate Limiting:** Configured (Redis-backed)
- [ ] **Fraud Detection:** ML model v1 trained and validated
- [ ] **GDPR/CCPA:** Data retention policies configured
- [ ] **54-ФЗ (OFД):** Fiscal integration tested
- [ ] **Sentry:** Error tracking configured with DSN
- [ ] **Monitoring:** Datadog/New Relic/CloudWatch configured

---

## 🚀 Deployment Steps

### 1. Pre-Deployment
```bash
# Clone repository
git clone https://github.com/yourorg/catvrf.git
cd catvrf

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci --omit=dev

# Build frontend
npm run build

# Copy environment file
cp .env.production .env

# Generate application key
php artisan key:generate --env=production
```

### 2. Database Setup
```bash
# Create databases
createdb catvrf_central
createdb catvrf_tenant_1

# Run migrations
php artisan migrate --env=production --force

# Seed initial data
php artisan db:seed --class=ProductionSeeder --env=production

# Verify migrations
php artisan migrate:status
```

### 3. Cache Warming
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache packages
php artisan package:discover --ansi

# Verify caches
ls -lah bootstrap/cache/
```

### 4. Queue Configuration
```bash
# Start supervisor for long-running queue worker
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start catvrf:*

# Or start queue worker manually
php artisan queue:work --env=production --daemon --sleep=3 --tries=3 --timeout=300

# Monitor queue
php artisan queue:monitor
```

### 5. Octane Server Startup
```bash
# Start Swoole HTTP server (Octane)
php artisan octane:start \
  --host=0.0.0.0 \
  --port=8000 \
  --workers=4 \
  --max-requests=500

# Or via systemd service (see systemd-setup.md)
sudo systemctl start catvrf-octane
sudo systemctl status catvrf-octane

# Verify server is running
curl -H "Accept: application/json" http://localhost:8000/up
```

### 6. Nginx Configuration
```nginx
# /etc/nginx/sites-available/catvrf.conf

upstream catvrf {
    server 127.0.0.1:8000;
    server 127.0.0.1:8001;  # Load balancing
    server 127.0.0.1:8002;
    keepalive 32;
}

server {
    listen 80;
    listen [::]:80;
    server_name api.catvrf.com;

    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.catvrf.com;

    # SSL certificates (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/api.catvrf.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.catvrf.com/privkey.pem;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Logging
    access_log /var/log/nginx/catvrf-access.log;
    error_log /var/log/nginx/catvrf-error.log warn;

    client_max_body_size 100M;
    client_body_timeout 120s;

    location / {
        proxy_pass http://catvrf;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header Connection "upgrade";
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_buffering off;
        proxy_request_buffering off;
    }

    # Static asset caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Disable access to hidden files
    location ~ /\. {
        deny all;
    }
}

# Enable HSTS preload list
# Add header: add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
```

### 7. Supervisor Configuration
```ini
# /etc/supervisor/conf.d/catvrf.conf

[program:catvrf-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /home/catvrf/app/artisan queue:work --env=production --daemon --sleep=3 --tries=3 --timeout=300
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/catvrf-queue.log
user=www-data

[program:catvrf-scheduler]
command=php /home/catvrf/app/artisan schedule:work --env=production
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/catvrf-scheduler.log
user=www-data

[group:catvrf]
programs=catvrf-queue,catvrf-scheduler
priority=999
```

### 8. Verification & Health Checks
```bash
# Health check endpoint
curl -s http://localhost:8000/up | jq .

# Check application status
php artisan health:check

# Verify database connectivity
php artisan tinker
>>> DB::connection('sqlite')->select('select 1')
>>> exit()

# Check Redis connectivity
redis-cli PING

# Monitor logs
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/catvrf-access.log

# Check payment webhook (Tinkoff test)
curl -X POST http://localhost:8000/api/internal/webhooks/payment/tinkoff \
  -H "Content-Type: application/json" \
  -d '{"OrderId":"123","Status":"CONFIRMED"}'
```

---

## 📊 Performance Optimization

### Caching Strategy
```php
// config/cache.php - Production settings
'default' => env('CACHE_DRIVER', 'redis'),
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'ttl' => 3600, // 1 hour
    ],
    'recommendation' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'ttl' => 300, // 5 minutes
    ],
    'fraud_score' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'ttl' => 60, // 1 minute
    ],
]
```

### Database Optimization
```sql
-- Create indices for frequently queried fields
CREATE INDEX idx_wallet_tenant_id ON wallets(tenant_id);
CREATE INDEX idx_payment_transactions_tenant_user ON payment_transactions(tenant_id, user_id);
CREATE INDEX idx_balance_transactions_wallet_created ON balance_transactions(wallet_id, created_at);
CREATE INDEX idx_fraud_attempts_user_timestamp ON fraud_attempts(user_id, created_at);

-- Enable query caching (PostgreSQL)
SET log_min_duration_statement = 1000; -- Log slow queries > 1s
```

### Octane Tuning
```bash
# Test with different worker counts
php artisan octane:start --workers=4 --max-requests=500
php artisan octane:start --workers=8 --max-requests=1000

# Monitor performance
ab -c 100 -n 10000 http://localhost:8000/api/health
wrk -t12 -c400 -d30s http://localhost:8000/api/health
```

---

## 🔒 Security Hardening

### Firewall Rules
```bash
# Allow only necessary ports
sudo ufw enable
sudo ufw allow 22/tcp      # SSH
sudo ufw allow 80/tcp      # HTTP (redirect to HTTPS)
sudo ufw allow 443/tcp     # HTTPS
sudo ufw allow 6379/tcp    # Redis (internal only)
sudo ufw default deny incoming
sudo ufw default allow outgoing
```

### SSL/TLS Setup
```bash
# Install Let's Encrypt certificate
sudo certbot certonly --nginx -d api.catvrf.com -d admin.catvrf.com

# Auto-renewal (cron job)
0 2 * * * /usr/bin/certbot renew --quiet

# Test SSL configuration
ssl-test https://api.catvrf.com
```

### Environment Variables
```env
# .env.production (NEVER commit this)
APP_DEBUG=false
APP_ENV=production
APP_KEY=base64:xxxxx

# Database
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=cookie
QUEUE_CONNECTION=redis

# Payment Gateways
TINKOFF_TERMINAL_KEY=xxxxx
TINKOFF_PASSWORD=xxxxx
SBERBANK_LOGIN=xxxxx
SBERBANK_PASSWORD=xxxxx

# Fraud Detection
FRAUD_ML_MODEL_PATH=/path/to/model.joblib
FRAUD_SCORE_THRESHOLD=0.7

# External Services
SENTRY_DSN=https://xxxxx@sentry.io/xxxxx
DOPPLER_TOKEN=xxxxx

# Rate Limiting
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# Mail
MAIL_FROM_ADDRESS=noreply@catvrf.com
MAIL_FROM_NAME="CatVRF Platform"
```

---

## 📈 Monitoring & Alerting

### Sentry Configuration
```php
// config/sentry.php
'dsn' => env('SENTRY_DSN'),
'traces_sample_rate' => 0.5,
'profiles_sample_rate' => 0.1,
'environment' => env('APP_ENV'),
```

### Datadog Integration
```php
// Monitor key metrics
Event::listen('payment.captured', function ($event) {
    \Datadog\DogStatsd::increment('catvrf.payment.captured');
});

\Datadog\DogStatsd::gauge('catvrf.wallet.balance', $balance);
\Datadog\DogStatsd::histogram('catvrf.api.response_time', $duration);
```

### Log Aggregation
```bash
# Forward logs to ELK Stack
# /etc/rsyslog.d/33-catvrf.conf
:programname, isequal, "catvrf" @elasticsearch:9200
```

---

## 🔄 Rollback Procedures

### Database Rollback
```bash
# Rollback last migration batch
php artisan migrate:rollback --env=production

# Rollback specific migration
php artisan migrate:rollback --env=production --step=1

# List migration status
php artisan migrate:status
```

### Application Rollback
```bash
# Git rollback
git revert HEAD~3 -m 1
git push production main

# Restart application
sudo systemctl restart catvrf-octane
sudo supervisorctl restart catvrf:*
```

---

## 📝 Maintenance Windows

### Scheduled Downtime
```bash
# Enable maintenance mode
php artisan down --secret=abc123

# Perform maintenance
php artisan migrate --env=production
php artisan cache:clear
php artisan config:cache

# Disable maintenance mode
php artisan up
```

### Zero-Downtime Deployment
```bash
# Using rolling deployment with multiple Octane servers
# 1. Start new server on port 8001
# 2. Update Nginx to route traffic to new server
# 3. Gracefully shutdown old server
# 4. Continue monitoring
```

---

## 🎯 Success Metrics

**Expected Results After Deployment:**

| Metric | Target | Status |
|--------|--------|--------|
| API Response Time (p95) | < 200ms | ✅ |
| Error Rate | < 0.1% | ✅ |
| Payment Success Rate | > 99.5% | ✅ |
| Uptime | > 99.9% | ✅ |
| Memory Usage | < 512MB/worker | ✅ |
| Cache Hit Rate | > 80% | ✅ |

---

**Deployment Complete!** 🎉

For issues, check logs: `storage/logs/laravel.log`, `nginx error.log`, or contact DevOps team.
