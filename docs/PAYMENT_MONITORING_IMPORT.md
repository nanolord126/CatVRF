# Payment Monitoring Setup Instructions

## Grafana Dashboard Import

### Method 1: Via Grafana UI

1. Open Grafana: http://localhost:3000 (default credentials: admin/admin)
2. Navigate to Dashboards → Import
3. Upload JSON file: `monitoring/grafana/dashboards/payment-monitoring.json`
4. Click "Import"
5. Dashboard will be available at: http://localhost:3000/d/catvrf-payment-monitoring

### Method 2: Via Grafana API

```bash
# Get API key from Grafana (Settings → API Keys → Add API Key)
GRAFANA_API_KEY="your_api_key_here"

# Import dashboard
curl -X POST http://localhost:3000/api/dashboards/db \
  -H "Authorization: Bearer $GRAFANA_API_KEY" \
  -H "Content-Type: application/json" \
  -d @monitoring/grafana/dashboards/payment-monitoring.json
```

### Dashboard Features

The payment monitoring dashboard includes:
- **Payment Status Rate** - Real-time payment status breakdown
- **Payment Failure Rate** - Gauge showing current failure rate
- **Gateway Errors** - Error rate per 5 minutes
- **Payment Gateway Latency** - P50, P95, P99 latency percentiles
- **Payment Volume by Gateway** - Transaction volume per gateway (Tinkoff, Tochka, Sberbank, SBP)
- **Fraud Detection Decisions** - Fraud check decision rates
- **Payment Queue Backlog** - Queue message count
- **Payment Reconciliation Discrepancies** - Reconciliation mismatch count

## Prometheus Alerts Setup

### Method 1: Update Prometheus Configuration

1. Edit `monitoring/prometheus/prometheus.yml`:
```yaml
rule_files:
  - "rules/*.yml"
  - "rules/payment_alerts.yml"
```

2. Restart Prometheus:
```bash
docker-compose restart prometheus
# or
systemctl restart prometheus
```

3. Verify alerts are loaded:
```bash
curl http://localhost:9090/api/v1/rules
```

### Method 2: Via Prometheus API

```bash
# Reload Prometheus configuration
curl -X POST http://localhost:9090/-/reload
```

### Alert Rules Available

The following alerts are configured in `monitoring/prometheus/rules/payment_alerts.yml`:

1. **HighPaymentFailureRate** - Warning when failure rate > 5% for 5 minutes
2. **CriticalPaymentFailureRate** - Critical when failure rate > 15% for 5 minutes
3. **HighPaymentGatewayLatency** - Warning when P95 latency > 5s for 5 minutes
4. **HighPaymentGatewayErrorRate** - Warning when error rate > 2% for 5 minutes
5. **PaymentReconciliationDiscrepancies** - Warning when discrepancies found
6. **HighFraudDetectionRisk** - Warning when high risk rate > 10% for 5 minutes
7. **PaymentQueueBacklog** - Warning when queue > 1000 messages for 5 minutes
8. **PaymentAmountAnomaly** - Warning when volume deviation > 50% for 10 minutes
9. **TinkoffHighFailureRate** - Gateway-specific alert for Tinkoff
10. **TochkaHighFailureRate** - Gateway-specific alert for Tochka
11. **SberbankHighFailureRate** - Gateway-specific alert for Sberbank
12. **SBPHighFailureRate** - Gateway-specific alert for SBP

### AlertManager Configuration

If using AlertManager, add to `monitoring/alertmanager/alertmanager.yml`:

```yaml
route:
  group_by: ['alertname', 'severity']
  group_wait: 10s
  group_interval: 10s
  repeat_interval: 1h
  receiver: 'default'

receivers:
  - name: 'default'
    email_configs:
      - to: 'alerts@catvrf.ru'
        from: 'prometheus@catvrf.ru'
        smarthost: 'smtp.catvrf.ru:587'
        auth_username: 'prometheus'
        auth_password: 'your_password'
```

## Environment Variables Setup

Add to `.env` file:

```bash
# === PAYMENT GATEWAY CONFIGURATION ===
PAYMENT_DEFAULT_GATEWAY=tinkoff

# Tinkoff Payment Gateway (Production)
TINKOFF_API_URL=https://securepay.tinkoff.ru/v2
TINKOFF_TERMINAL_KEY=your_production_terminal_key
TINKOFF_SECRET_KEY=your_production_secret_key
TINKOFF_TIMEOUT=30

# Tochka Bank (B2B Production)
TOCHKA_API_URL=https://enter.tochka.com/api
TOCHKA_TOKEN=your_production_token
TOCHKA_TIMEOUT=30

# Sberbank (Сбер Production)
SBERBANK_API_URL=https://securepayments.sberbank.ru/payment/rest
SBERBANK_USERNAME=your_production_username
SBERBANK_PASSWORD=your_production_password
SBERBANK_TIMEOUT=30

# SBP (Система быстрых платежей Production)
SBP_API_URL=https://api.nspk.ru
SBP_API_KEY=your_production_api_key
SBP_TIMEOUT=30

# === PAYMENT ENGINE CONFIGURATION ===
PAYMENT_NEW_ENGINE_ENABLED=false
PAYMENT_ASYNC_FRAUD_ENABLED=true
PAYMENT_CIRCUIT_BREAKER_ENABLED=true
PAYMENT_FRAUD_AMOUNT_THRESHOLD=5000000
PAYMENT_IDEMPOTENCY_TTL=86400
PAYMENT_GATEWAY_TIMEOUT=5
PAYMENT_GATEWAY_MAX_RETRIES=3
PAYMENT_CIRCUIT_BREAKER_THRESHOLD=5
PAYMENT_CIRCUIT_BREAKER_TIMEOUT=60

# === PAYMENT QUEUE CONFIGURATION ===
PAYMENT_FRAUD_CHECK_QUEUE=payment-fraud-check
PAYMENT_RECONCILIATION_QUEUE=payment-reconciliation

# === PAYMENT RECONCILIATION ===
PAYMENT_RECONCILIATION_ENABLED=true
PAYMENT_RECONCILIATION_SCHEDULE=0 2 * * *
PAYMENT_RECONCILIATION_LOOKBACK_DAYS=7

# === PAYMENT WEBHOOK CONFIGURATION ===
PAYMENT_WEBHOOK_VERIFY_HMAC=true
PAYMENT_WEBHOOK_REPLAY_WINDOW=300
```

### Sandbox Credentials for Testing

Replace with actual sandbox credentials from payment providers:

```bash
# Tinkoff Sandbox
TINKOFF_TERMINAL_KEY=test_your_sandbox_terminal_key
TINKOFF_SECRET_KEY=test_your_sandbox_secret_key

# Tochka Sandbox
TOCHKA_TOKEN=your_sandbox_token

# Sberbank Sandbox
SBERBANK_USERNAME=your_sandbox_username
SBERBANK_PASSWORD=your_sandbox_password

# SBP Sandbox
SBP_API_KEY=your_sandbox_api_key
```

## Verification Steps

### 1. Verify Prometheus Metrics

```bash
# Check if payment metrics are being exported
curl http://localhost:8000/metrics | grep catvrf_payment
```

Expected output:
```
catvrf_payment_status_total{gateway="tinkoff",status="succeeded"} 1234
catvrf_payment_status_total{gateway="sberbank",status="failed"} 5
catvrf_payment_gateway_latency_seconds_bucket{gateway="tinkoff",le="0.5"} 1000
```

### 2. Verify Grafana Dashboard

1. Open Grafana: http://localhost:3000
2. Navigate to Dashboards → CatVRF Payment Monitoring
3. Verify all panels show data
4. Check time range is set to "Last 1 hour"

### 3. Verify Alerts

1. Open Prometheus: http://localhost:9090
2. Navigate to Alerts
3. Verify all payment alerts are listed
4. Check alert states (firing/inactive)

### 4. Test Payment Flow

```bash
# Test payment creation
php artisan tinker --execute="
\$service = app(\App\Services\Payment\PaymentService::class);
\$result = \$service->initiatePayment(1000, 'tinkoff', 'test@example.com');
print_r(\$result);
"
```

## Troubleshooting

### Dashboard shows no data

- Check if Prometheus is scraping metrics: `curl http://localhost:9090/api/v1/targets`
- Verify metrics are being exported: `curl http://localhost:8000/metrics`
- Check Grafana datasource configuration

### Alerts not firing

- Verify alert rules are loaded: `curl http://localhost:9090/api/v1/rules`
- Check alert evaluation interval in Prometheus config
- Verify alert expression matches metric names

### Payment reconciliation job failing

- Check job logs: `php artisan queue:work --queue=payment-reconciliation --verbose`
- Verify gateway credentials are correct
- Check gateway API status
