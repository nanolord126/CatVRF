# Payment Vertical Setup Guide

**Version:** 1.0  
**Date:** 2026-04-29  
**Vertical:** Payment

## Overview

This guide covers the complete setup of the Payment vertical in CatVRF, including:
- Database migrations
- Environment configuration
- Scheduled jobs
- Prometheus/Grafana monitoring
- Sandbox testing

## 1. Database Migrations

### Run Payment Table Migration

```bash
php artisan migrate
```

This will create the `payments` table with the following structure:
- `id` - Primary key
- `tenant_id` - Tenant relationship (foreign key)
- `payable_type`, `payable_id` - Polymorphic relationship to Order/Booking
- `uuid` - Unique payment identifier
- `amount`, `currency` - Payment amount (default: RUB)
- `status` - pending, succeeded, failed, partially_paid, refunded
- `gateway` - yookassa, tinkoff, tochka, etc.
- `gateway_transaction_id` - Gateway transaction reference
- `gateway_response` - JSON response from gateway
- `error_message` - Error details if failed
- `paid_at`, `failed_at`, `refunded_at` - Timestamps
- `metadata` - Additional JSON data
- `timestamps`, `softDeletes` - Standard Laravel timestamps

## 2. Environment Configuration

### Required Environment Variables

Add the following variables to your `.env` file (or use Doppler in production):

```bash
# === PAYMENT ENGINE CONFIGURATION ===
# Enable new PaymentEngineService (gradual rollout)
PAYMENT_NEW_ENGINE_ENABLED=false

# Enable async fraud detection
PAYMENT_ASYNC_FRAUD_ENABLED=true

# Enable circuit breaker
PAYMENT_CIRCUIT_BREAKER_ENABLED=true

# Fraud amount threshold (in kopecks, default: 5,000,000 = 50,000 RUB)
PAYMENT_FRAUD_AMOUNT_THRESHOLD=5000000

# Idempotency key TTL in seconds (default: 86400 = 24 hours)
PAYMENT_IDEMPOTENCY_TTL=86400

# Gateway timeout in seconds (default: 5)
PAYMENT_GATEWAY_TIMEOUT=5

# Maximum gateway retry attempts (default: 3)
PAYMENT_GATEWAY_MAX_RETRIES=3

# Circuit breaker failure threshold (default: 5)
PAYMENT_CIRCUIT_BREAKER_THRESHOLD=5

# Circuit breaker timeout in seconds (default: 60)
PAYMENT_CIRCUIT_BREAKER_TIMEOUT=60

# === PAYMENT QUEUE CONFIGURATION ===
# Queue for payment fraud checks
PAYMENT_FRAUD_CHECK_QUEUE=payment-fraud-check

# Queue for payment reconciliation
PAYMENT_RECONCILIATION_QUEUE=payment-reconciliation

# === PAYMENT RECONCILIATION ===
# Enable daily reconciliation with gateways
PAYMENT_RECONCILIATION_ENABLED=false

# Reconciliation schedule (cron format, default: 0 2 * * * = 2 AM daily)
PAYMENT_RECONCILIATION_SCHEDULE=0 2 * * *

# Lookback period in days for reconciliation (default: 7)
PAYMENT_RECONCILIATION_LOOKBACK_DAYS=7

# === PAYMENT WEBHOOK CONFIGURATION ===
# Enable HMAC signature verification
PAYMENT_WEBHOOK_VERIFY_HMAC=true

# Replay protection window in seconds (default: 300 = 5 minutes)
PAYMENT_WEBHOOK_REPLAY_WINDOW=300

# === PAYMENT GATEWAY CONFIGURATION ===
# Default gateway
PAYMENT_DEFAULT_GATEWAY=tinkoff

# Tinkoff Payment Gateway
TINKOFF_TERMINAL_KEY=your_tinkoff_terminal_key
TINKOFF_SECRET_KEY=your_tinkoff_secret_key
TINKOFF_API_URL=https://securepay.tinkoff.ru/v2
TINKOFF_TIMEOUT=30

# Tochka Bank (B2B)
TOCHKA_API_URL=https://enter.tochka.com/api
TOCHKA_TOKEN=your_tochka_token
TOCHKA_TIMEOUT=30

# Sberbank (Сбер)
SBERBANK_API_URL=https://securepayments.sberbank.ru/payment/rest
SBERBANK_USERNAME=your_sberbank_username
SBERBANK_PASSWORD=your_sberbank_password
SBERBANK_TIMEOUT=30

# SBP (Система быстрых платежей)
SBP_API_URL=https://api.nspk.ru
SBP_API_KEY=your_sbp_api_key
SBP_TIMEOUT=30
```

### Sandbox Testing Credentials

**IMPORTANT:** Always test with sandbox credentials before production!

#### Tinkoff Sandbox
1. Register at https://business.tinkoff.ru
2. Get sandbox credentials from your account
3. Set environment variables:
   ```bash
   TINKOFF_TERMINAL_KEY=test_your_sandbox_terminal_key
   TINKOFF_SECRET_KEY=test_your_sandbox_secret_key
   TINKOFF_API_URL=https://securepay.tinkoff.ru/v2
   ```

#### Tochka Sandbox
1. Register at https://enter.tochka.com
2. Get sandbox credentials from your account
3. Set environment variables:
   ```bash
   TOCHKA_API_URL=https://enter.tochka.com/api
   TOCHKA_TOKEN=your_sandbox_token
   ```

#### Sberbank Sandbox
1. Register at https://developer.sberbank.ru
2. Get sandbox credentials from your account
3. Set environment variables:
   ```bash
   SBERBANK_API_URL=https://securepayments.sberbank.ru/payment/rest
   SBERBANK_USERNAME=your_sandbox_username
   SBERBANK_PASSWORD=your_sandbox_password
   ```

#### SBP Sandbox
1. Register at https://nspk.ru/partners
2. Get sandbox credentials from your account
3. Set environment variables:
   ```bash
   SBP_API_URL=https://api.nspk.ru
   SBP_API_KEY=your_sandbox_api_key
   ```

## 3. Scheduled Jobs

### Payment Scheduled Jobs

The following scheduled jobs are automatically configured in `app/Console/Kernel.php`:

#### Daily Payout Job
- **Schedule:** Daily at 08:00 UTC
- **Queue:** default
- **Description:** Process pending payouts for all tenants

#### Batch Payout Job
- **Schedule:** Every 2 hours
- **Queue:** default
- **Description:** Process batch withdrawals from mass payout queue

#### Payment Reconciliation Job
- **Schedule:** Configurable via `PAYMENT_RECONCILIATION_SCHEDULE` (default: 0 2 * * *)
- **Queue:** `payment-reconciliation` (configurable via `PAYMENT_RECONCILIATION_QUEUE`)
- **Description:** Daily payment reconciliation with gateways (Tinkoff, Tochka, Sberbank, SBP)
- **Enabled:** Via `PAYMENT_RECONCILIATION_ENABLED=true`

#### Cleanup Expired Idempotency Records Job
- **Schedule:** Daily at 00:00 UTC
- **Queue:** default
- **Description:** Remove expired payment idempotency records (older than 24h)

### Enable Scheduled Jobs

Ensure Laravel scheduler is running on your server:

```bash
# Add to crontab
* * * * * php /path-to-your-project/artisan schedule:run >> /dev/null 2>&1
```

Or use Supervisor for production:

```ini
[program:laravel-scheduler]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-your-project/artisan schedule:run
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path-to-your-project/storage/logs/scheduler.log
```

## 4. Prometheus/Grafana Monitoring

### Prometheus Alerts Configuration

Alert rules are defined in `monitoring/prometheus/rules/payment_alerts.yml`:

#### Key Alerts

1. **HighPaymentFailureRate** - Warning when failure rate > 5%
2. **CriticalPaymentFailureRate** - Critical when failure rate > 15%
3. **HighPaymentGatewayLatency** - Warning when P95 latency > 5s
4. **HighPaymentGatewayErrorRate** - Warning when error rate > 2%
5. **PaymentReconciliationDiscrepancies** - Warning when discrepancies found
6. **HighFraudDetectionRisk** - Warning when high risk rate > 10%
7. **PaymentQueueBacklog** - Warning when queue > 1000 messages
8. **PaymentAmountAnomaly** - Warning when volume deviation > 50%
9. **SberbankHighFailureRate** - Gateway-specific alert
10. **SBPHighFailureRate** - Gateway-specific alert

### Load Alerts into Prometheus

Add to `monitoring/prometheus/prometheus.yml`:

```yaml
rule_files:
  - "rules/*.yml"
  - "rules/payment_alerts.yml"
```

Reload Prometheus configuration:

```bash
# If using Docker
docker exec prometheus prometheus --config.file=/etc/prometheus/prometheus.yml

# Or reload via API
curl -X POST http://localhost:9090/-/reload
```

### Grafana Dashboard

Import the dashboard from `monitoring/grafana/dashboards/payment-monitoring.json`:

1. Open Grafana
2. Go to Dashboards → Import
3. Upload the JSON file
4. Select Prometheus datasource
5. Save

**Dashboard includes:**
- Payment Status Rate (by status)
- Payment Failure Rate gauge
- Gateway Errors gauge
- Payment Gateway Latency (p50, p95, p99)
- Payment Volume by Gateway
- Fraud Detection Decisions Rate
- Payment Queue Backlog
- Payment Reconciliation Discrepancies

## 5. Testing Checklist

### Sandbox Testing Before Production

- [ ] Run migrations: `php artisan migrate`
- [ ] Configure sandbox credentials in `.env`
- [ ] Test payment creation with small amount (100 RUB)
- [ ] Verify webhook endpoints receive callbacks
- [ ] Check payment status transitions (pending → succeeded/failed)
- [ ] Test fraud detection with known test cards
- [ ] Verify idempotency key handling (duplicate requests)
- [ ] Test circuit breaker behavior (simulate gateway failures)
- [ ] Run reconciliation job manually:
  ```bash
  php artisan tinker
  >>> App\Jobs\PaymentReconciliationJob::dispatch(1);
  ```
- [ ] Verify Prometheus metrics are being exported
- [ ] Check Grafana dashboard displays data
- [ ] Test alert notifications (Slack/Email)

### Production Readiness Checklist

- [ ] Switch to production credentials
- [ ] Enable reconciliation: `PAYMENT_RECONCILIATION_ENABLED=true`
- [ ] Verify webhook HMAC verification
- [ ] Set appropriate rate limits
- [ ] Configure circuit breaker thresholds
- [ ] Enable payment fraud ML: `PAYMENT_ASYNC_FRAUD_ENABLED=true`
- [ ] Set up backup payment gateway (failover)
- [ ] Configure alert notifications in Alertmanager
- [ ] Review reconciliation discrepancies daily (first week)
- [ ] Monitor payment latency and error rates
- [ ] Set up log aggregation for payment logs
- [ ] Configure audit logging for compliance

## 6. API Endpoints

### Payment Endpoints

Payment endpoints are defined in `routes/api/payment.api.php`:

```
POST   /api/payments                    - Create payment
GET    /api/payments/{uuid}             - Get payment by UUID
POST   /api/payments/{uuid}/refund      - Refund payment
POST   /api/payments/webhook/yookassa   - YooKassa webhook
POST   /api/payments/webhook/tinkoff    - Tinkoff webhook
GET    /api/payments/reconcile          - Manual reconciliation trigger
```

## 7. Troubleshooting

### Common Issues

#### Migration Fails
```bash
# Check pending migrations
php artisan migrate:status

# Force rollback and retry
php artisan migrate:rollback
php artisan migrate
```

#### Payment Not Creating
- Check gateway credentials in `.env`
- Verify queue worker is running: `php artisan queue:work`
- Check logs: `tail -f storage/logs/laravel.log`

#### Webhook Not Receiving
- Verify webhook URL is publicly accessible
- Check firewall allows gateway IPs
- Verify HMAC signature verification
- Check `PAYMENT_WEBHOOK_VERIFY_HMAC=true`

#### Reconciliation Shows Discrepancies
- Check gateway credentials are correct
- Verify time zone settings (UTC)
- Check gateway API rate limits
- Review reconciliation logs

#### Prometheus Metrics Not Showing
- Verify Prometheus is scraping metrics endpoint
- Check metrics route: `GET /metrics`
- Verify `PROMETHEUS_ROUTE_ENABLED=true`
- Check Redis connection for metrics storage

## 8. Security Considerations

- **Never commit credentials** to version control
- **Use Doppler** for production secrets (Zero Trust 2026)
- **Enable webhook HMAC verification** in production
- **Implement rate limiting** on payment endpoints
- **Monitor fraud detection** alerts closely
- **Regular reconciliation** to detect discrepancies
- **Audit logging** for compliance (152-FZ, PCI-DSS)

## 9. Compliance Notes

### 152-FZ (Personal Data)
- Payment amounts and transaction IDs are not PII
- User IDs must be anonymized in external logs
- Audit logs retention: 12 months (configurable via `AUDIT_RETENTION_MONTHS`)

### PCI-DSS
- Never store full card numbers
- Use tokenization where possible
- Encrypt sensitive data at rest
- Regular security audits required

## 10. Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Review monitoring: Grafana dashboard
- Check alerts: Alertmanager
- Contact: DevOps team (Slack: #payments-devops)
