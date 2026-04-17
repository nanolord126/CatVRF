# Prometheus Metrics Deployment Guide for CatVRF

This guide covers the complete deployment of Prometheus monitoring stack for CatVRF ML pipeline.

## Overview

CatVRF uses Prometheus for monitoring:
- ML model performance (retrain duration, AUC, shadow mode)
- Feature drift detection (PSI, KS, JS divergence)
- Quota usage and alerts
- Fraud ML metrics (inference latency, fraud scores)

## Prerequisites

- Docker & Docker Compose
- Redis instance (already configured in CatVRF)
- CatVRF application deployed
- Grafana instance (optional, for visualization)

## Quick Start with Docker Compose

### 1. Deploy Prometheus + Grafana Stack

```bash
# Clone the repository
cd /opt/kotvrf/CatVRF

# Start the monitoring stack
docker-compose -f docker-compose.monitoring.yml up -d
```

This will start:
- Prometheus on port 9090
- Grafana on port 3000
- Alertmanager on port 9093
- Node Exporter on port 9100

### 2. Configure CatVRF Environment

Add to your `.env` file:

```bash
# Prometheus Configuration
PROMETHEUS_STORAGE_DRIVER=redis
PROMETHEUS_REDIS_CONNECTION=default
PROMETHEUS_ROUTE_ENABLED=true
PROMETHEUS_ROUTE_PREFIX=metrics
PROMETHEUS_ROUTE_MIDDLEWARE=auth:landlord,throttle:metrics
PROMETHEUS_NAMESPACE=catvrf
```

### 3. Import Grafana Dashboard

1. Open Grafana at http://localhost:3000 (default: admin/admin)
2. Navigate to Dashboards → Import
3. Upload `docs/grafana-dashboard-catvrf-ml-drift-quota.json`
4. Select Prometheus datasource
5. Save dashboard

### 4. Configure Alertmanager

1. Update `docs/alertmanager-config.yml` with your Slack/PagerDuty credentials
2. Update `docs/prometheus-alert-rules.yml` if needed
3. Restart Alertmanager:
```bash
docker-compose -f docker-compose.monitoring.yml restart alertmanager
```

## Manual Deployment (Production)

### 1. Install Prometheus

```bash
# Download Prometheus
wget https://github.com/prometheus/prometheus/releases/download/v2.48.0/prometheus-2.48.0.linux-amd64.tar.gz
tar xvfz prometheus-2.48.0.linux-amd64.tar.gz
cd prometheus-2.48.0.linux-amd64

# Create user and directories
sudo useradd --no-create-home --shell /bin/false prometheus
sudo mkdir /etc/prometheus
sudo mkdir /var/lib/prometheus

# Copy binaries
sudo cp prometheus /usr/local/bin/
sudo cp promtool /usr/local/bin/
sudo chown prometheus:prometheus /usr/local/bin/prometheus
sudo chown prometheus:prometheus /usr/local/bin/promtool

# Copy configuration files
sudo cp -r consoles /etc/prometheus
sudo cp -r console_libraries /etc/prometheus
sudo cp prometheus.yml /etc/prometheus
sudo chown -R prometheus:prometheus /etc/prometheus
sudo chown -R prometheus:prometheus /var/lib/prometheus
```

### 2. Configure Prometheus

Copy `docs/prometheus-scrape-config.yml` to `/etc/prometheus/scrape-configs.yml` and update `/etc/prometheus/prometheus.yml`:

```yaml
global:
  scrape_interval: 15s
  evaluation_interval: 15s
  external_labels:
    cluster: 'catvrf-production'
    environment: 'production'

scrape_configs:
  - job_name: 'catvrf-app'
    file_sd_configs:
      - files:
          - '/etc/prometheus/scrape-configs.yml'
    basic_auth:
      username: 'prometheus'
      password: '${PROMETHEUS_SCRAPING_PASSWORD}'

alerting:
  alertmanagers:
    - static_configs:
        - targets:
            - 'localhost:9093'

rule_files:
  - '/etc/prometheus/rules/*.yml'
```

### 3. Copy Alert Rules

```bash
sudo cp docs/prometheus-alert-rules.yml /etc/prometheus/rules/
sudo chown prometheus:prometheus /etc/prometheus/rules/prometheus-alert-rules.yml
```

### 4. Create Systemd Service

Create `/etc/systemd/system/prometheus.service`:

```ini
[Unit]
Description=Prometheus
Wants=network-online.target
After=network-online.target

[Service]
User=prometheus
Group=prometheus
Type=simple
ExecStart=/usr/local/bin/prometheus \
  --config.file /etc/prometheus/prometheus.yml \
  --storage.tsdb.path /var/lib/prometheus \
  --web.console.templates=/etc/prometheus/consoles \
  --web.console.libraries=/etc/prometheus/console_libraries \
  --web.listen-address=0.0.0.0:9090 \
  --web.external-url=http://prometheus.catvrf.internal:9090

Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable prometheus
sudo systemctl start prometheus
```

### 5. Install Alertmanager

```bash
# Download Alertmanager
wget https://github.com/prometheus/alertmanager/releases/download/v0.26.0/alertmanager-0.26.0.linux-amd64.tar.gz
tar xvfz alertmanager-0.26.0.linux-amd64.tar.gz
cd alertmanager-0.26.0.linux-amd64

# Create directories
sudo mkdir /etc/alertmanager
sudo mkdir /var/lib/alertmanager

# Copy binaries
sudo cp alertmanager /usr/local/bin/
sudo cp amtool /usr/local/bin/
sudo chown prometheus:prometheus /usr/local/bin/alertmanager
sudo chown prometheus:prometheus /usr/local/bin/amtool

# Copy configuration
sudo cp alertmanager.yml /etc/alertmanager/
sudo chown -R prometheus:prometheus /etc/alertmanager
sudo chown -R prometheus:prometheus /var/lib/alertmanager
```

Copy `docs/alertmanager-config.yml` to `/etc/alertmanager/alertmanager.yml` and update with your Slack/PagerDuty credentials.

### 6. Create Alertmanager Systemd Service

Create `/etc/systemd/system/alertmanager.service`:

```ini
[Unit]
Description=Alertmanager
Wants=network-online.target
After=network-online.target

[Service]
User=prometheus
Group=prometheus
Type=simple
ExecStart=/usr/local/bin/alertmanager \
  --config.file /etc/alertmanager/alertmanager.yml \
  --storage.path /var/lib/alertmanager \
  --web.listen-address=0.0.0.0:9093 \
  --web.external-url=http://alertmanager.catvrf.internal:9093

Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable alertmanager
sudo systemctl start alertmanager
```

### 7. Configure Nginx for Metrics Endpoint

Add to your Nginx configuration:

```nginx
# Prometheus metrics endpoint
location /metrics {
    auth_basic "Prometheus Metrics";
    auth_basic_user_file /etc/nginx/.htpasswd.prometheus;
    
    # Rate limiting
    limit_req_zone $binary_remote_addr zone=metrics:10m rate=10r/s;
    limit_req zone=metrics burst=20 nodelay;
    
    # Only allow from Prometheus server
    allow 10.0.0.100;  # Prometheus server IP
    deny all;
    
    proxy_pass http://127.0.0.1:8000;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
}
```

Create htpasswd file:
```bash
sudo htpasswd -c /etc/nginx/.htpasswd.prometheus prometheus
```

## Security Best Practices

1. **Protect /metrics endpoint**: Use authentication and IP whitelisting
2. **Use TLS**: Always use HTTPS for metrics scraping
3. **Separate credentials**: Store scraping passwords in vault/Doppler
4. **Network isolation**: Run Prometheus in separate network segment
5. **Firewall rules**: Only allow Prometheus server to scrape metrics

## Monitoring

### Verify Metrics are Exported

```bash
# Test metrics endpoint (requires authentication)
curl -u prometheus:password https://catvrf.internal/metrics

# Check Prometheus targets
curl http://localhost:9090/api/v1/targets
```

### Verify Alert Rules

```bash
# Check if rules are loaded
curl http://localhost:9090/api/v1/rules

# Test alert rules
curl http://localhost:9090/api/v1/alerts
```

### Grafana Dashboard

After importing the dashboard, you should see:
- ML Model Retrain Duration
- ML Model AUC Score
- Feature Drift PSI Score
- Quota Usage Ratio
- Fraud ML Inference Latency
- Feature Drift Detected Rate
- AI Tokens Consumption Rate
- Fraud Blocked by ML Rate

## Troubleshooting

### Metrics not appearing in Prometheus

1. Check if /metrics endpoint is accessible:
```bash
curl -u prometheus:password https://catvrf.internal/metrics
```

2. Check Prometheus logs:
```bash
sudo journalctl -u prometheus -f
```

3. Verify scrape configuration:
```bash
curl http://localhost:9090/api/v1/targets
```

### High cardinality warnings

1. Check for high-cardinality labels in metrics
2. Verify blocked_labels configuration in config/prometheus.php
3. Use label sanitization in PrometheusMetricsService

### Alertmanager not receiving alerts

1. Check Alertmanager logs:
```bash
sudo journalctl -u alertmanager -f
```

2. Verify Prometheus → Alertmanager connection:
```bash
curl http://localhost:9090/api/v1/alertmanagers
```

3. Check alert rule evaluation:
```bash
curl http://localhost:9090/api/v1/rules
```

## Maintenance

### Backup Metrics Data

```bash
# Backup Prometheus TSDB
sudo tar -czf prometheus-backup-$(date +%Y%m%d).tar.gz /var/lib/prometheus
```

### Retention Management

Edit `/etc/prometheus/prometheus.yml`:
```yaml
storage:
  tsdb:
    retention.time: 15d
    retention.size: 10GB
```

### Update Alert Rules

1. Edit `/etc/prometheus/rules/prometheus-alert-rules.yml`
2. Validate rules:
```bash
promtool check rules /etc/prometheus/rules/prometheus-alert-rules.yml
```
3. Reload Prometheus:
```bash
sudo systemctl reload prometheus
```

## References

- [Prometheus Documentation](https://prometheus.io/docs/)
- [Alertmanager Documentation](https://prometheus.io/docs/alerting/latest/alertmanager/)
- [Grafana Documentation](https://grafana.com/docs/)
- [Spatie Laravel Prometheus](https://github.com/spatie/laravel-prometheus)

## Support

For issues or questions, contact:
- DevOps: devops@catvrf.internal
- ML Team: ml-team@catvrf.internal
- On-call: oncall@catvrf.internal
