# Account Protection System - Deployment Guide

**Version:** 1.0  
**Date:** April 19, 2026

---

## Prerequisites

### Required Packages

```bash
# Already in project
composer require laravel/sanctum
composer require spatie/laravel-permission

# Optional: For AWS Rekognition
composer require aws/aws-sdk-php
```

### Database Migrations

```bash
php artisan migrate
```

**Migrations to run:**
- `2026_04_19_000003_create_account_recovery_logs_table.php`
- `2026_04_19_000004_create_user_devices_table.php`
- `2026_04_19_000005_alter_webauthn_credentials_add_backup_codes.php`

### Environment Configuration

Add to `.env` (use Doppler in production):

```env
# AI Face Verification
AI_FACE_PROVIDER=yandex
YANDEX_VISION_API_KEY=your_api_key
YANDEX_FOLDER_ID=your_folder_id

# Account Protection
MAX_FAILED_AUTH_ATTEMPTS=5
ACCOUNT_LOCK_DURATION_HOURS=24
VELOCITY_WINDOW_SECONDS=300
MAX_REQUESTS_PER_WINDOW=10

# Recovery
RECOVERY_OTP_TTL_SECONDS=600
RECOVERY_COOLDOWN_HOURS=24
BACKUP_CODES_COUNT=10
HIGH_RISK_THRESHOLD=0.80
MEDIUM_RISK_THRESHOLD=0.40

# Audit
AUDIT_LOG_TO_CLICKHOUSE=true
AUDIT_CLICKHOUSE_TABLE=security_events
AUDIT_RETENTION_DAYS=90
```

---

## ClickHouse Setup

### Create Security Events Table

```sql
CREATE TABLE IF NOT EXISTS catvrf_analytics.security_events (
    event_type String,
    channel String,
    correlation_id UUID,
    user_id Nullable(UInt64),
    tenant_id Nullable(UInt64),
    data String,
    created_at DateTime64(3)
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(created_at)
ORDER BY (created_at, event_type)
TTL created_at + INTERVAL 90 DAY;
```

### Configure Laravel ClickHouse Connection

Add to `config/database.php`:

```php
'clickhouse' => [
    'driver' => 'clickhouse',
    'host' => env('CLICKHOUSE_HOST', '127.0.0.1'),
    'port' => env('CLICKHOUSE_PORT', '8123'),
    'database' => env('CLICKHOUSE_DATABASE', 'catvrf_analytics'),
    'username' => env('CLICKHOUSE_USERNAME', 'default'),
    'password' => env('CLICKHOUSE_PASSWORD', ''),
],
```

---

## Middleware Registration

Add to `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ... existing middleware
    'suspicious.activity' => \App\Http\Middleware\SuspiciousActivityMiddleware::class,
    'require.passkey' => \App\Http\Middleware\RequirePasskeyOrHighVerification::class,
];
```

Apply to routes:

```php
Route::middleware(['auth:sanctum', 'suspicious.activity'])->group(function () {
    // Sensitive routes
});

Route::middleware(['auth:sanctum', 'require.passkey'])->group(function () {
    // Highly sensitive routes (withdrawal, tenant changes)
});
```

---

## Redis Configuration

Ensure Redis is configured for caching:

```env
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CACHE_DB=0
```

**Keys used:**
- `account_locked:{user_id}` - Account lock status (24h TTL)
- `recovery_cooldown:{user_id}` - Recovery cooldown (24h TTL)
- `recovery_otp:{recovery_log_id}` - OTP hash (10 min TTL)
- `failed_auth:{user_id}:{ip}` - Failed auth counter (15 min TTL)
- `velocity:{user_id}:{ip}:{action}` - Velocity counter (5 min TTL)
- `ip_reputation:{ip}` - IP reputation cache (1h TTL)
- `fraud_score:{user_id}` - User fraud score (24h TTL)

---

## Queue Configuration

Security events are dispatched to queues. Ensure Horizon is configured:

```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'security'],
            'balance' => 'auto',
            'processes' => 10,
        ],
    ],
],
```

Start Horizon:

```bash
php artisan horizon
```

---

## Monitoring Setup

### Prometheus Metrics

Add to your Prometheus exporter:

```php
// Custom metrics for security
- security_account_locks_total
- security_recovery_initiations_total
- security_recovery_completions_total
- security_deepfake_detections_total
- security_failed_authentications_total
- security_new_device_registrations_total
```

### Grafana Dashboard

Import the dashboard from `docs/grafana/security-dashboard.json` (create this file).

Key panels:
1. Account lock rate (last 24h)
2. Recovery success rate
3. Deepfake detection rate
4. Failed authentication rate
5. New device registrations
6. Risk score distribution

### Sentry Integration

Add security context to Sentry:

```php
Sentry\configureScope(function (Scope $scope) {
    $scope->setContext('security', [
        'account_locked' => $isLocked,
        'fraud_score' => $fraudScore,
        'device_trusted' => $isTrusted,
    ]);
});
```

---

## Performance Optimization

### Caching Strategy

1. **Device Fingerprints** - Cache for 1 hour
2. **IP Reputation** - Cache for 1 hour
3. **Fraud Scores** - Cache for 24 hours
4. **User Sessions** - Cache for session duration

### Database Indexes

Ensure these indexes exist:

```sql
-- account_recovery_logs
CREATE INDEX idx_recovery_user_tenant ON account_recovery_logs(user_id, tenant_id);
CREATE INDEX idx_recovery_status ON account_recovery_logs(status);
CREATE INDEX idx_recovery_risk_score ON account_recovery_logs(risk_score);
CREATE INDEX idx_recovery_initiated_at ON account_recovery_logs(initiated_at);

-- user_devices
CREATE INDEX idx_devices_user_tenant ON user_devices(user_id, tenant_id);
CREATE INDEX idx_devices_fingerprint ON user_devices(fingerprint);
CREATE INDEX idx_devices_trusted ON user_devices(is_trusted);
CREATE INDEX idx_devices_last_seen ON user_devices(last_seen_at);

-- webauthn_credentials
CREATE INDEX idx_credentials_user_tenant ON webauthn_credentials(user_id, tenant_id);
CREATE INDEX idx_credentials_compromised ON webauthn_credentials(is_compromised);
```

### Rate Limiting

Configure rate limiting in `config/rate-limiting.php`:

```php
'security' => [
    'recovery_init' => '3,60',  // 3 per minute
    'recovery_verify' => '10,60',  // 10 per minute
    'face_verify' => '5,60',  // 5 per minute
],
```

---

## Security Hardening

### HTTPS Only

Add to `app/Providers/AppServiceProvider.php`:

```php
public function boot(): void
{
    if (app()->environment('production')) {
        URL::forceScheme('https');
    }
}
```

### HSTS

Add to Nginx config:

```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

### CSP

Add to Nginx config:

```nginx
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';" always;
```

### Encrypted Backups

Ensure database backups are encrypted:

```bash
# Using pg_dump with encryption
pg_dump -U user dbname | gzip | gpg --encrypt --recipient backup@example.com > backup.sql.gz.gpg
```

---

## Testing Deployment

### Smoke Test

```bash
curl -X GET https://api.catvrf.ru/api/v1/security/status \
  -H "Authorization: Bearer $TOKEN"
```

Expected response:
```json
{
  "account_locked": false,
  "fraud_score": 0.0,
  "passkey_count": 0,
  "device_count": 0,
  "face_verified": false,
  "security_level": "low"
}
```

### Recovery Flow Test

```bash
# 1. Initiate recovery
curl -X POST https://api.catvrf.ru/api/v1/recovery/init \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","method":"email"}'

# 2. Verify OTP (get from logs in test)
curl -X POST https://api.catvrf.ru/api/v1/recovery/{log_id}/verify \
  -H "Content-Type: application/json" \
  -d '{"verification_code":"123456"}'

# 3. Complete recovery
curl -X POST https://api.catvrf.ru/api/v1/recovery/{log_id}/complete \
  -H "Content-Type: application/json" \
  -d '{"credential_id":"...","credential_public_key":"...","counter":0}'
```

### Load Test

```bash
k6 run k6/crash-test-security.js
```

---

## Rollback Plan

If issues occur after deployment:

1. **Disable new middleware:**
   ```bash
   # Comment out middleware in routes/api.php
   ```

2. **Revert migrations:**
   ```bash
   php artisan migrate:rollback --step=3
   ```

3. **Clear caches:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

4. **Restart Horizon:**
   ```bash
   php artisan horizon:terminate
   ```

5. **Monitor logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## Post-Deployment Checklist

- [ ] All migrations ran successfully
- [ ] Redis connection verified
- [ ] ClickHouse table created
- [ ] Horizon running and processing queues
- [ ] Prometheus metrics exporting
- [ ] Grafana dashboard accessible
- [ ] Sentry error tracking configured
- [ ] Rate limiting working
- [ ] HTTPS enforced
- [ ] HSTS header present
- [ ] CSP header present
- [ ] Smoke test passed
- [ ] Recovery flow tested
- [ ] Load test completed
- [ ] Team notified
- [ ] Documentation updated

---

## Support

**Emergency Contact:** security@catvrf.ru  
**Documentation:** https://docs.catvrf.ru/security  
**Slack:** #security-alerts

---

**Maintained by:** CatVRF DevOps Team  
**Last Updated:** April 19, 2026
