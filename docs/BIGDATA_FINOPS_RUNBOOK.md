# FinOps Runbook — CatVRF BigData Cost Monitoring

**Version:** 1.0 (April 2026)
**Owner:** FinOps Team + BigData Engineering
**Target:** Analytics cost ≤ 3–5% of GMV

---

## 1. Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    BigData Cost Monitoring                   │
├─────────────┬──────────────┬──────────────┬────────────────┤
│  Cloud API  │  ClickHouse  │  Prometheus  │   Grafana      │
│  (billing)  │  (storage)   │  (metrics)   │  (dashboards)  │
├─────────────┼──────────────┼──────────────┼────────────────┤
│ AWS/GCP/    │ billing_raw  │ cost_ metrics│ Cost Overview  │
│ Azure/Self  │ cost_agg_    │ exported by  │ CH Deep Dive   │
│             │ daily        │ CostExporter │ Unit Economics  │
├─────────────┼──────────────┼──────────────┼────────────────┤
│             │ cost_attr    │ Alertmanager │                │
│             │ anomalies    │ (PagerDuty/  │                │
│             │ recommend.   │ Slack/TG)    │                │
└─────────────┴──────────────┴──────────────┴────────────────┘
```

### API Usage

```php
// Daily breakdown
BigData::cost()->getDailyBreakdown(CarbonImmutable::now());

// Seller attribution — "Your analytics costs X"
BigData::cost()->getSellerAttribution($tenantId, $sellerId);

// Monthly prediction
BigData::cost()->predictMonthly();

// Optimization recommendations
BigData::cost()->optimizeRecommendations();

// Auto-apply safe optimizations
BigData::cost()->autoOptimize();

// Detect anomalies
BigData::cost()->detectAnomalies();

// Unit economics
BigData::cost()->getUnitEconomics(30);

// Full snapshot
BigData::cost()->getSnapshot();
```

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/metrics/bigdata-cost` | Prometheus scrape (no auth) |
| GET | `/api/bigdata/cost/snapshot` | Full cost snapshot |
| GET | `/api/bigdata/cost/daily?date=YYYY-MM-DD` | Daily breakdown |
| GET | `/api/bigdata/cost/timeseries?start=&end=` | Time series |
| GET | `/api/bigdata/cost/seller/{id}?tenant_id=1` | Seller attribution |
| GET | `/api/bigdata/cost/top-sellers?tenant_id=1` | Top expensive sellers |
| GET | `/api/bigdata/cost/predict` | Monthly prediction |
| GET | `/api/bigdata/cost/recommendations` | Optimization recommendations |
| POST | `/api/bigdata/cost/auto-optimize` | Trigger auto-optimization |
| GET | `/api/bigdata/cost/clickhouse` | ClickHouse deep dive |
| GET | `/api/bigdata/cost/kafka` | Kafka cost metrics |
| GET | `/api/bigdata/cost/spark-ml` | Spark/ML cost & ROI |
| GET | `/api/bigdata/cost/unit-economics` | Unit economics |
| GET | `/api/bigdata/cost/anomalies` | Open anomalies |
| POST | `/api/bigdata/cost/detect-anomalies` | Run anomaly detection |

---

## 2. Scheduled Jobs

| Job | Schedule | Queue | Description |
|-----|----------|-------|-------------|
| `ImportBillingDataJob` | Hourly | bigdata-cost | Import cloud billing data |
| `BudgetEnforcementJob` | Every 30 min | bigdata-cost | Check budget + detect anomalies |
| `AutoOptimizeJob` | Daily 06:00 | bigdata-cost | Analyze + auto-apply safe optimizations |

---

## 3. Alert Rules & Response

### Critical Alerts

| Alert | Condition | Action |
|-------|-----------|--------|
| `BigDataBudgetCriticalOver` | Utilization ≥ 120% | Page VP Engineering, scale down non-critical |
| `BigDataBudgetExceeded` | Utilization ≥ 100% | Notify FinOps Slack, review spend |
| `BigDataStorageGrowthHigh` | Growth > 15%/week | Enable tiered storage, reduce TTL |
| `BigDataDailyCostSpike` | Cost > 150% of 7d avg | Investigate query patterns |

### Warning Alerts

| Alert | Condition | Action |
|-------|-----------|--------|
| `BigDataBudgetApproaching` | Utilization > 80% | Prepare optimization plan |
| `BigDataCompressionRatioLow` | Ratio < 5x | Apply ZSTD(3) codec |
| `BigDataCostAnomalyDetected` | Any anomaly | Investigate in Grafana |
| `BigDataCostToGmvRatioHigh` | Cost/GMV > 5% | Reduce analytics scope |
| `BigDataHighOptimizableCost` | Optimizable > $50/day | Run auto-optimization |

---

## 4. Optimization Playbook

### 4.1 ClickHouse Storage

**Problem:** Storage growing too fast
**Actions:**
1. Check compression ratio: `BigData::cost()->getCompressionRatios()`
2. If < 10x → apply ZSTD(3): `ALTER TABLE ch_raw_events MODIFY COLUMN properties CODEC(ZSTD(3))`
3. Enable tiered storage: hot SSD (7d) → warm S3 (90d) → cold Glacier
4. Reduce TTL on raw_events: 90d → 30d (aggregated data preserved)
5. Schedule `OPTIMIZE FINAL` only during off-peak (Sunday 03:00)

### 4.2 Kafka

**Problem:** Kafka broker costs too high
**Actions:**
1. Reduce retention to 7 days max for raw topics
2. Raw data is already in ClickHouse within minutes
3. Consider compaction for event-sourced topics
4. Monitor `bytes_in` / `bytes_out` ratio

### 4.3 Spark / ML

**Problem:** ML training costs exceed GMV uplift
**Actions:**
1. Use spot/preemptible instances only for Spark jobs
2. Check CLV training ROI: `cost_to_gmv_ratio` should be < 5%
3. Batch predictions instead of real-time where possible
4. Reduce training frequency (weekly vs daily)

### 4.4 Query Optimization

**Problem:** One seller consuming 40%+ of compute
**Actions:**
1. Identify top queries: `BigData::cost()->getTopExpensiveQueries($date)`
2. Add bloom_filter indices on `seller_id`, `event_type`
3. Create materialized views for repeated query patterns
4. Rate-limit expensive dashboard refreshes per seller

---

## 5. Weekly FinOps Review Checklist

- [ ] Review "Big Data Cost Overview" dashboard
- [ ] Check budget utilization trend
- [ ] Identify top-5 most expensive sellers
- [ ] Review compression ratios (target > 10x)
- [ ] Check storage growth rate (target < 10%/week)
- [ ] Review CLV training ROI
- [ ] Apply pending optimization recommendations
- [ ] Verify billing import is working (no gaps)
- [ ] Review cost-to-GMV ratio (target ≤ 5%)
- [ ] Update monthly budget if GMV changed

---

## 6. ClickHouse Cost Tables

| Table | Purpose | TTL |
|-------|---------|-----|
| `ch_billing_raw` | Raw cloud billing import | 400 days |
| `ch_cost_aggregated_daily` | Daily aggregated (MV) | 730 days |
| `ch_cost_attribution` | Per seller/context attribution | 730 days |
| `ch_clickhouse_cost_metrics` | CH internal cost metrics | 365 days |
| `ch_kafka_cost_metrics` | Kafka broker/throughput | 365 days |
| `ch_spark_ml_cost_metrics` | Spark/ML job cost & ROI | 365 days |
| `ch_cost_anomalies` | Detected anomalies | 180 days |
| `ch_cost_optimization_recommendations` | Optimization recs | 180 days |

---

## 7. Key Metrics (Prometheus)

| Metric | Description | Target |
|--------|-------------|--------|
| `catvrf_bigdata_cost_daily_total_usd` | Daily total cost | ≤ budget/30 |
| `catvrf_bigdata_cost_budget_utilization_percent` | Budget utilization | ≤ 80% |
| `catvrf_bigdata_cost_compression_ratio` | CH compression ratio | > 10x |
| `catvrf_bigdata_cost_storage_growth_rate_percent` | Storage growth | < 10%/week |
| `catvrf_bigdata_cost_unit_cost_per_1m_events` | Cost per 1M events | < $1 |
| `catvrf_bigdata_cost_unit_cost_per_query` | Cost per query | < $0.0005 |
| `catvrf_bigdata_cost_unit_cost_to_gmv_ratio` | Cost/GMV ratio | ≤ 5% |
| `catvrf_bigdata_cost_anomaly_open_count` | Open anomalies | 0 |

---

## 8. Environment Variables

```env
# Cost Monitoring
BIGDATA_COST_MONITORING_ENABLED=true
BIGDATA_CLOUD_PROVIDER=self_hosted  # aws|gcp|azure|self_hosted

# Budget
BIGDATA_MONTHLY_BUDGET_USD=5000
BIGDATA_BUDGET_WARNING=0.80
BIGDATA_BUDGET_CRITICAL=1.00
BIGDATA_BUDGET_EMERGENCY=1.20
BIGDATA_GMV_MAX_RATIO=0.05

# Currency
BIGDATA_USD_TO_RUB=95

# AWS (if cloud_provider=aws)
BIGDATA_AWS_ACCESS_KEY_ID=
BIGDATA_AWS_SECRET_ACCESS_KEY=
BIGDATA_AWS_REGION=us-east-1

# GCP (if cloud_provider=gcp)
BIGDATA_GCP_PROJECT_ID=
BIGDATA_GCP_BILLING_ACCOUNT_ID=
BIGDATA_GCP_ACCESS_TOKEN=

# Azure (if cloud_provider=azure)
BIGDATA_AZURE_SUBSCRIPTION_ID=
BIGDATA_AZURE_TENANT_ID=
BIGDATA_AZURE_CLIENT_ID=
BIGDATA_AZURE_CLIENT_SECRET=

# Self-hosted pricing
BIGDATA_SELF_HOSTED_SERVER_COUNT=3
BIGDATA_SELF_HOSTED_STORAGE_GB=2000
BIGDATA_SELF_HOSTED_NETWORK_GB_DAILY=50
BIGDATA_SELF_HOSTED_COMPUTE_COST_PER_HOUR=0.10
BIGDATA_SELF_HOSTED_STORAGE_COST_PER_GB_MONTH=0.023
BIGDATA_SELF_HOSTED_NETWORK_COST_PER_GB=0.01

# Auto-optimization
BIGDATA_AUTO_OPTIMIZE_ENABLED=true

# Notifications
BIGDATA_COST_NOTIFY_TELEGRAM=false
BIGDATA_COST_NOTIFY_SLACK=false
BIGDATA_COST_NOTIFY_PAGERDUTY=false
BIGDATA_COST_TELEGRAM_CHAT_ID=
BIGDATA_COST_SLACK_WEBHOOK=
BIGDATA_COST_PAGERDUTY_KEY=
```

---

## 9. Emergency Procedures

### Budget Critical (>120%)

1. **Immediate:** Disable non-critical Spark jobs
2. **5 min:** Reduce ClickHouse query concurrency
3. **15 min:** Enable auto-scale-down on Kafka consumers
4. **30 min:** FinOps team review — identify root cause
5. **1 hour:** Apply emergency TTL reduction on raw_events

### Cost Spike (unexpected)

1. Check `ch_cost_anomalies` for root cause
2. Identify which service/seller caused the spike
3. If seller overuse → rate-limit their queries
4. If storage spike → check for failed merges / parts explosion
5. If compute spike → check for runaway queries in `system.query_log`

---

## 10. File Map

```
modules/BigData/
├── Domain/
│   ├── Entities/          CostBreakdown, CostAttribution, OptimizationRecommendation
│   ├── Enums/             CostEnums (CloudProvider, CostCategory, BoundedContext, BudgetStatus, OptimizationType, RiskLevel, CostAnomalyType, SparkJobType)
│   ├── Events/            BudgetExceeded, CostAnomalyDetected, OptimizationApplied
│   ├── Interfaces/        CostRepositoryInterface, CloudBillingAdapterInterface
│   ├── ValueObjects/      BudgetThreshold
│   └── DTOs/              DailyCostDTO, SellerCostDTO, CostPredictionDTO
├── Application/
│   ├── Services/          BigDataCostFacade
│   ├── Jobs/              ImportBillingDataJob, AutoOptimizeJob, BudgetEnforcementJob
│   └── Listeners/         CostNotificationListener
├── Infrastructure/
│   ├── Adapters/          AWSCostExplorerAdapter, GCPBillingAdapter, AzureCostManagementAdapter, SelfHostedBillingAdapter
│   ├── Repositories/      ClickHouseCostRepository
│   └── Exporters/         BigDataCostExporter
├── Presentation/
│   ├── Http/Controllers/  CostController
│   └── Routes/            cost.php
├── CostMonitoringServiceProvider.php
├── BigDataServiceProvider.php  (updated: registers CostMonitoringServiceProvider)
└── config/bigdata.php          (updated: cost section added)

database/clickhouse/
└── cost_schema.sql             (8 tables + 1 MV)

monitoring/
├── grafana/dashboards/
│   ├── bigdata-cost-overview.json
│   ├── bigdata-cost-deep-dive.json
│   └── bigdata-unit-economics-roi.json
└── alertmanager/rules/
    └── bigdata_cost_alerts.yml

docker-compose.bigdata-finops.yml
docs/BIGDATA_FINOPS_RUNBOOK.md
```
