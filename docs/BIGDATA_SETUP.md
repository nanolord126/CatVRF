# Big Data Vertical Setup Guide

**Version:** 2.0  
**Date:** April 28, 2026  
**Scale:** 50M+ events/day, 500M+ historical records  
**Infrastructure:** Cloud-native (no Docker required)

---

## Overview

The Big Data vertical provides enterprise-grade analytics infrastructure for CatVRF marketplace:

- **Real-time event tracking** via Redis Streams / Confluent Cloud + ClickHouse
- **Seller analytics** with daily metrics
- **CLV predictions** using ML models
- **A/B testing** with statistical analysis
- **Feature store** for recommendations and matching
- **Batch processing** via PySpark

---

## Architecture

### Lambda Architecture (Cloud-Native)

```
┌─────────────────────────────────────────────────────┐
│  Speed Layer                                        │
│  Redis Streams / Confluent Cloud → ClickHouse       │
│  (real-time ingestion via HTTP interface)           │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│  Batch Layer                                        │
│  PySpark → ClickHouse (daily aggregates)             │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│  Serving Layer                                       │
│  ClickHouse + Materialized Views                     │
└─────────────────────────────────────────────────────┘
```

### Infrastructure Stack (No Docker)

| Component | Local (Dev) | Cloud (Production) |
|-----------|-------------|-------------------|
| OLAP Storage | ClickHouse in WSL | ClickHouse Cloud |
| Event Streaming | Redis Streams | Confluent Cloud REST Proxy |
| Cache/Streams | Redis (local) | Redis Cloud |
| Object Storage | Local filesystem | AWS S3 / MinIO Cloud |
| Monitoring | Laravel logs | Grafana Cloud |
| Batch Processing | PySpark (local) | Databricks / EMR |

---

## Installation

### 1. Install ClickHouse (WSL on Windows)

```bash
# Open WSL
wsl -d Ubuntu

# Install ClickHouse
sudo apt-get install -y apt-transport-https ca-certificates curl gnupg
echo 'deb [signed-by=/usr/share/keyrings/clickhouse-keyring.gpg] https://packages.clickhouse.com/deb stable main' | sudo tee /etc/apt/sources.list.d/clickhouse.list
sudo apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y clickhouse-server clickhouse-client

# Start ClickHouse server
sudo -u clickhouse /usr/bin/clickhouse server --config-file /etc/clickhouse-server/config.xml &

# Verify
clickhouse-client --query 'SELECT version()'
```

For Linux/macOS, use the same commands without WSL prefix.

### 2. Create Database and Run Migrations

```bash
# Create database
clickhouse-client --query 'CREATE DATABASE IF NOT EXISTS catvrf_bigdata'

# Run migrations
clickhouse-client --database catvrf_bigdata --queries-file database/clickhouse/bigdata_schema.sql

# Verify
clickhouse-client --database catvrf_bigdata --query 'SHOW TABLES'
```

### 3. Configure Environment

```bash
cp .env.example.bigdata .env.bigdata
```

Required environment variables:

```env
# ClickHouse (WSL local or ClickHouse Cloud)
CLICKHOUSE_HOST=localhost
CLICKHOUSE_PORT=8123
CLICKHOUSE_DATABASE=catvrf_bigdata
CLICKHOUSE_USERNAME=default
CLICKHOUSE_PASSWORD=

# Event Streaming
KAFKA_ENABLED=true
KAFKA_MODE=redis_streams          # 'redis_streams' or 'rest_proxy'
KAFKA_TOPIC=bigdata_events

# Redis (for Redis Streams fallback)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=
REDIS_PORT=6379
```

### 4. Register Service Provider

Already added to `config/app.php`:

```php
'providers' => [
    Modules\BigData\BigDataServiceProvider::class,
],
```

### 5. Verify Installation

```php
// Test ClickHouse connection via HTTP
$pdo = file_get_contents('http://localhost:8123/?query=' . urlencode('SELECT version()'));
echo "ClickHouse version: " . $pdo;
```

---

## Cloud Services Setup (Production)

### Confluent Cloud (Kafka)

1. Sign up at https://confluent.cloud/ (free tier: 100K messages/month)
2. Create a cluster
3. Get API Key from **Settings > API keys**
4. Get REST Proxy URL from **Cluster settings**

```env
KAFKA_MODE=rest_proxy
KAFKA_BROKERS=pk-xxxxx.us-east-1.aws.confluent.cloud:9092
KAFKA_REST_PROXY_URL=https://pkc-xxxxx.us-east-1.aws.confluent.cloud:8082
KAFKA_USERNAME=<api-key>
KAFKA_PASSWORD=<api-secret>
```

### ClickHouse Cloud

1. Sign up at https://clickhouse.cloud/ (free tier: 1 service)
2. Create a service
3. Get connection details

```env
CLICKHOUSE_HOST=your-cluster.clickhouse.cloud
CLICKHOUSE_PORT=8443
CLICKHOUSE_DATABASE=catvrf_bigdata
CLICKHOUSE_USERNAME=default
CLICKHOUSE_PASSWORD=<your-password>
```

### Redis Cloud

1. Sign up at https://redis.com/try-free/ (30MB free)
2. Create a database
3. Get connection details

```env
REDIS_HOST=redis-xxxxx.c1.us-east-1.ec2.cloud.redislabs.com
REDIS_PORT=12345
REDIS_PASSWORD=<your-password>
```

### Grafana Cloud

1. Sign up at https://grafana.com/auth/sign-up/cloud/ (free: 3 dashboards)
2. Get API Key from **Configuration > API Keys**

```env
GRAFANA_URL=https://your-org.grafana.net
GRAFANA_API_KEY=<your-api-key>
```

### AWS S3 (Cold Storage)

1. Create S3 bucket
2. Configure IAM user with S3 access

```env
BIGDATA_STORAGE_DRIVER=s3
AWS_ACCESS_KEY_ID=<key>
AWS_SECRET_ACCESS_KEY=<secret>
AWS_DEFAULT_REGION=us-east-1
BIGDATA_S3_BUCKET=catvrf-bigdata
```

---

## Usage

### Tracking Events

```php
use Modules\BigData\Application\Services\BigDataFacade;
use Modules\BigData\Domain\Enums\EventType;

// Using Facade
BigDataFacade::trackEvent(
    eventType: EventType::OrderPlaced,
    tenantId: 1,
    userId: 123,
    orderId: 456,
    monetaryValue: 99.99,
    properties: ['payment_method' => 'card']
);
```

### Querying Metrics

```php
// Seller metrics
$metrics = BigDataFacade::sellerMetrics(
    tenantId: 1, sellerId: 456,
    startDate: now()->subDays(30), endDate: now()
);

// Daily GMV
$gmv = BigDataFacade::dailyGMV(
    tenantId: 1,
    startDate: now()->subDays(30), endDate: now()
);

// CLV prediction
$clv = BigDataFacade::clv(tenantId: 1, userId: 123);
```

### A/B Testing

```php
$results = BigDataFacade::abTestResults(
    testId: 'test_123', tenantId: 1
);
// Returns: conversion rates, p-value, Bayesian probability, uplift
```

---

## Event Streaming Modes

### Mode 1: Redis Streams (Default — No Kafka needed)

Events are published to Redis Streams and consumed by `KafkaConsumerJob`:

```bash
# Run consumer as queue worker
php artisan queue:work bigdata-kafka --daemon
```

Redis Streams are a built-in feature of Redis — no additional software needed.

### Mode 2: Confluent Cloud REST Proxy

Events are published via HTTP to Confluent Cloud:

```env
KAFKA_MODE=rest_proxy
KAFKA_REST_PROXY_URL=https://pkc-xxxxx.confluent.cloud:8082
```

---

## PySpark Jobs

### Feature Store Job

```bash
spark-submit \
  --jars clickhouse-jdbc-0.4.6-all.jar \
  --driver-memory 2g \
  modules/BigData/python/jobs/feature_store_job.py \
  --date 2026-04-28 \
  --tenant_id 1 \
  --clickhouse_url jdbc:clickhouse://localhost:8123/catvrf_bigdata
```

### CLV Training Job

```bash
spark-submit \
  --jars clickhouse-jdbc-0.4.6-all.jar \
  --driver-memory 4g \
  modules/BigData/python/jobs/clv_training_job.py \
  --start_date 2026-01-01 \
  --end_date 2026-04-28 \
  --tenant_id 1 \
  --output_path /data/training/clv_features.parquet
```

### A/B Test Evaluation Job

```bash
spark-submit \
  --jars clickhouse-jdbc-0.4.6-all.jar \
  modules/BigData/python/jobs/abtest_evaluation_job.py \
  --test_id test_123 \
  --tenant_id 1
```

---

## Scheduled Jobs

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('bigdata:feature-store')
        ->dailyAt('02:00')
        ->onQueue('bigdata-spark');

    $schedule->command('bigdata:clv-train')
        ->weekly()->sundays()->at('03:00')
        ->onQueue('bigdata-spark');

    $schedule->command('bigdata:abtest-evaluate')
        ->hourly()
        ->onQueue('bigdata-spark');
}
```

---

## Monitoring

### Health Check

```php
$status = BigDataFacade::health();
// Returns: status, version, ping, tables
```

### Filament Dashboard

Navigate to `/admin/big-data/health` for:
- ClickHouse health status
- Table row counts
- Event volume metrics
- Redis Streams lag

### Query Explorer

Navigate to `/admin/big-data/query` for:
- Safe SQL query interface
- Example queries
- Result visualization

---

## ClickHouse Server Management (WSL)

### Start ClickHouse

```bash
wsl -d Ubuntu -- bash -c "sudo -u clickhouse /usr/bin/clickhouse server --config-file /etc/clickhouse-server/config.xml &"
```

### Stop ClickHouse

```bash
wsl -d Ubuntu -- bash -c "clickhouse-client --query 'SYSTEM SHUTDOWN'"
```

### Check Status

```bash
# From Windows
Invoke-WebRequest -Uri "http://localhost:8123/ping"

# From WSL
clickhouse-client --query 'SELECT uptime(), version()'
```

### Backup

```bash
clickhouse-client --database catvrf_bigdata --query "BACKUP DATABASE catvrf_bigdata TO S3 's3://catvrf-bigdata/backups/'"
```

---

## Performance Tuning

### ClickHouse

```sql
SET max_memory_usage = 10000000000;  -- 10GB
SET compression_codec = 'ZSTD';
SET compression_level = 3;
```

### Redis Streams

```env
# Consumer batch size
KAFKA_MAX_MESSAGES=1000

# Consumer group
KAFKA_CONSUMER_GROUP_ID=bigdata_consumer
```

---

## Data Retention

```sql
-- Raw events: 90 days
TTL created_at + INTERVAL 90 DAY

-- Daily metrics: 2 years
TTL metric_date + INTERVAL 730 DAY

-- CLV predictions: 1 year
TTL prediction_date + INTERVAL 365 DAY
```

---

## Security

### PII Anonymization

Events marked as PII-sensitive are automatically anonymized before external processing.

### ClickHouse Access Control

```sql
CREATE USER bigdata_user IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT ON catvrf_bigdata.* TO bigdata_user;
```

### Confluent Cloud Authentication

```env
KAFKA_USERNAME=<api-key>
KAFKA_PASSWORD=<api-secret>
```

---

## Troubleshooting

### ClickHouse Connection Failed

```bash
# Check server is running
wsl -d Ubuntu -- bash -c "ps aux | grep clickhouse"

# Check from Windows
Invoke-WebRequest -Uri "http://localhost:8123/ping"

# Restart server
wsl -d Ubuntu -- bash -c "sudo -u clickhouse /usr/bin/clickhouse server --config-file /etc/clickhouse-server/config.xml &"
```

### Redis Streams Consumer Lag

```bash
# Check stream length
redis-cli XLEN bigdata:events:bigdata_events

# Check consumer group
redis-cli XINFO GROUPS bigdata:events:bigdata_events

# Reset consumer group
redis-cli XGROUP DESTROY bigdata:events:bigdata_events bigdata_consumer
```

### Spark Job Out of Memory

```bash
--executor-memory 16g
--driver-memory 8g
--conf spark.sql.shuffle.partitions=200
```

---

## Production Checklist

- [ ] ClickHouse configured (local cluster or ClickHouse Cloud)
- [ ] Event streaming configured (Redis Streams or Confluent Cloud)
- [ ] Redis configured (local or Redis Cloud)
- [ ] S3/MinIO for Parquet storage
- [ ] Grafana Cloud monitoring
- [ ] Backup strategy for ClickHouse
- [ ] Load testing (k6)
- [ ] Security audit
- [ ] GDPR compliance review

---

## Scaling Guidelines

### 50M+ Events/Day

- ClickHouse: 3 nodes, 32GB RAM (or ClickHouse Cloud Business)
- Kafka: Confluent Cloud Standard cluster
- Redis: Redis Cloud Pro
- Spark: Databricks / EMR

### 500M+ Events/Day

- ClickHouse: 9 nodes, 64GB RAM (or ClickHouse Cloud Enterprise)
- Kafka: Confluent Cloud Dedicated cluster
- Redis: Redis Cloud Enterprise
- Spark: Databricks / EMR with auto-scaling

---

## References

- [ClickHouse Documentation](https://clickhouse.com/docs)
- [Confluent Cloud REST Proxy](https://docs.confluent.io/platform/current/kafka-rest/api.html)
- [Redis Streams](https://redis.io/docs/data-types/streams/)
- [PySpark Documentation](https://spark.apache.org/docs/latest/api/python)
- [ClickHouse Cloud](https://clickhouse.cloud/)
- [Grafana Cloud](https://grafana.com/docs/grafana-cloud/)

---

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Check ClickHouse: `http://localhost:8123/ping`
- Check Redis: `redis-cli ping`
- Create issue in GitHub repository
