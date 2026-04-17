# CatVRF Grafana Dashboards

**Version:** 2026.04.17  
**Project:** CatVRF - AI-powered Healthcare Marketplace  
**Purpose:** Production-ready Grafana dashboards for monitoring ML drift, quota usage, fraud detection, and model lifecycle across all business verticals.

---

## Overview

This directory contains production-ready Grafana dashboards for CatVRF's ML infrastructure monitoring. All dashboards use Prometheus metrics exported via Spatie Laravel Prometheus and are designed for high-scale production environments (5k-50k+ RPS).

## Available Dashboards

| Dashboard | JSON File | UID | Purpose | Target Audience |
|-----------|-----------|-----|---------|-----------------|
| **CatVRF ML Drift & Quota Monitor** | `catvrf-ml-drift-quota-monitor.json` | `catvrf-ml-drift-quota-monitor` | Main dashboard for ML drift, quota, and fraud monitoring across all verticals | ML Engineers, DevOps, SRE |
| **Medical Healthcare ML Monitor** | `medical-healthcare-ml-monitor.json` | `catvrf-medical-healthcare-ml-monitor` | Specialized dashboard for Medical vertical with healthcare-specific metrics | Medical Team, ML Engineers |
| **ML Retrain & Model Lifecycle** | `ml-retrain-model-lifecycle.json` | `catvrf-ml-retrain-lifecycle` | Model retrain and lifecycle monitoring | ML Engineers, Data Scientists |
| **Universal Vertical ML Monitor** | `universal-vertical-ml-monitor.json` | `catvrf-universal-vertical-ml-monitor` | Universal dashboard with vertical selector for all 64 verticals | All Vertical Teams, ML Engineers |

---

## Quick Start

### Prerequisites

1. **Grafana** installed and running (v9.5+ recommended)
2. **Prometheus** data source configured in Grafana
3. **Spatie Laravel Prometheus** installed in CatVRF
4. **Prometheus exporter** running on `/metrics` endpoint

### Import Dashboard

1. Navigate to Grafana → Dashboards → New → Import
2. Choose one of the following methods:
   - **Upload JSON file**: Select the JSON file from this directory
   - **Paste JSON**: Copy and paste the JSON content
   - **Import by ID**: (if published to Grafana.com)

3. Configure the data source:
   - Select your Prometheus data source
   - Update the variable `DS_PROMETHEUS` if needed

4. Click **Import**

---

## Dashboard Details

### 1. CatVRF ML Drift & Quota Monitor

**Purpose:** Main dashboard for monitoring ML drift, quota usage, and fraud detection across all business verticals.

**Features:**
- **Row 1: Overall ML Pipeline Health**
  - ML Retrain Duration (p95) with timeout alerts (> 30 min)
  - Max Feature Drift Score with PSI/KS thresholds
  - Active Model AUC with version tracking

- **Row 2: Feature Drift by Vertical (Heatmap)**
  - Feature Drift Heatmap for all 127 verticals
  - Top 10 Drifted Features table
  - Color-coded severity (green < 0.1, yellow 0.1-0.25, red > 0.25)

- **Row 3: Quota & AI Consumption**
  - Tenant Quota Usage Ratio by vertical (alert > 85%)
  - AI Tokens Consumed (last 24h) by vertical
  - Quota Exceeded Events tracking
  - Fraud ML Inference Latency

- **Row 4: Alerts & Trends**
  - Feature Drift Events by severity
  - Fraud Block Rate vs Quota Abuse
  - Vertical Drift Score over time
  - Fraud Score Distribution

**Use Cases:**
- Daily ML health checks
- Proactive drift detection
- Quota optimization
- Fraud monitoring

---

### 2. Medical Healthcare ML Monitor

**Purpose:** Specialized dashboard for Medical vertical with healthcare-specific metrics and compliance tracking.

**Features:**
- **Row 1: Medical AI Diagnosis Drift**
  - AI Diagnosis Frequency PSI/KS/Combined drift
  - Drift event tracking (1h window)

- **Row 2: Health Score Distribution Shift**
  - Health Score PSI/KS/Combined drift
  - Distribution shift monitoring

- **Row 3: Emergency Protocol & Quota Burn Rate**
  - Emergency Event Rate drift
  - Medical Quota Usage (alert > 85%)
  - AI Tokens Consumed by model
  - Quota burn rate by operation

- **Row 4: Fraud Score Distribution & Model Performance**
  - Fraud Score Distribution (Medical)
  - Fraud ML Inference Latency
  - Fraud Blocked by ML
  - Medical Vertical Drift Score

**Use Cases:**
- Medical compliance monitoring (152-ФЗ, ФЗ-323)
- Healthcare-specific drift detection
- Emergency protocol monitoring
- Medical fraud detection

---

### 3. ML Retrain & Model Lifecycle

**Purpose:** Monitor ML model retraining process and model lifecycle management.

**Features:**
- **Row 1: Retrain Duration & Performance**
  - Last Retrain Duration (p95) with timeout alerts
  - Tenants Processed count
  - Retrain Success Rate
  - Active Model AUC

- **Row 2: Model Version History & Promotion**
  - Model Version Events by action
  - Model Promotion History
  - Active Model Version
  - Current Model AUC
  - Version Updates (24h)
  - Retrains (24h)

- **Row 3: Training Metrics & Feature Importance**
  - Training Metrics (Accuracy, Precision, Recall, F1)
  - Model AUC over time
  - Current Training Metrics table
  - Last Training Duration

- **Row 4: Shadow Mode & Comparison**
  - Shadow Model Promotions (24h)
  - Model Rollbacks (24h)
  - Active Model AUC (Shadow comparison)

**Use Cases:**
- Retrain job monitoring
- Model promotion tracking
- Training performance analysis
- Shadow mode validation

---

### 4. Universal Vertical ML Monitor

**Purpose:** Universal dashboard with vertical selector for monitoring any of the 64 business verticals.

**Features:**
- **Variable Selector:** Choose any vertical from dropdown (auto-populated from metrics)
- **Row 1: Vertical Overview**
  - Max Feature Drift Score for selected vertical
  - Quota Usage Ratio for selected vertical
  - Drift Events (1h)
  - AI Tokens (24h)

- **Row 2: Feature Drift Metrics**
  - Feature PSI Over Time
  - Feature KS p-value Over Time
  - Combined Drift Score Over Time
  - Top 10 Drifted Features table

- **Row 3: Quota & AI Consumption**
  - Quota Usage by Resource Type
  - AI Tokens Consumed (1h) by model
  - Quota Exceeded Events (1h)
  - Quota Burn Rate (24h)

- **Row 4: Fraud Detection**
  - Fraud Score Distribution
  - Fraud ML Inference Latency
  - Fraud Blocked by ML (1h)
  - Vertical Drift Score

**Use Cases:**
- Monitor any vertical without creating separate dashboards
- Quick vertical health checks
- Vertical-specific quota monitoring
- Cross-vertical comparison

**How to Use:**
1. Import the dashboard
2. Select your vertical from the "Vertical" dropdown (top of dashboard)
3. Dashboard automatically filters all metrics for selected vertical
4. Switch between verticals as needed

---

## Metrics Reference

All metrics use the `catvrf` namespace and are exported via Spatie Laravel Prometheus.

### ML Retrain Metrics

- `catvrf_ml_retrain_duration_seconds` (Histogram)
- `catvrf_ml_retrain_success_total` (Counter)
- `catvrf_ml_retrain_tenants_processed_total` (Counter)

### Model Performance Metrics

- `catvrf_ml_model_auc_current` (Gauge)
- `catvrf_ml_model_promoted_timestamp` (Gauge)
- `catvrf_ml_model_version_updated_total` (Counter)
- `catvrf_ml_model_training_metric` (Gauge)

### Feature Drift Metrics

- `catvrf_feature_drift_psi` (Gauge)
- `catvrf_feature_drift_ks` (Gauge)
- `catvrf_feature_drift_js` (Gauge)
- `catvrf_feature_drift_combined` (Gauge)
- `catvrf_feature_drift_detected_total` (Counter)
- `catvrf_vertical_drift_score` (Gauge)
- `catvrf_vertical_drift_detected_total` (Counter)

### Quota Metrics

- `catvrf_quota_usage_ratio` (Gauge)
- `catvrf_quota_exceeded_total` (Counter)
- `catvrf_ai_tokens_consumed_total` (Counter)

### Fraud Detection Metrics

- `catvrf_fraud_ml_inference_latency_seconds` (Histogram)
- `catvrf_fraud_score_distribution` (Histogram)
- `catvrf_fraud_blocked_by_ml_total` (Counter)

---

## Alert Thresholds

### Critical Alerts (PagerDuty/Slack)

- **High feature drift (PSI > 0.25)**
- **Significant KS drift (p-value ≤ 0.05)**
- **High combined drift score (> 0.7)**
- **Quota exceeded (> 95%)**
- **Model AUC degradation (< 0.90)**
- **Retrain timeout (> 30 min)**

### Warning Alerts (Email/Slack)

- **Moderate feature drift (PSI > 0.1)**
- **High quota usage (> 85%)**
- **Model AUC warning (< 0.92)**
- **Retrain duration warning (> 15 min)**

### Info Alerts (Slack only)

- **Model version updated**
- **Significant drift event**

---

## Configuration

### Dashboard Settings

- **Refresh Interval:** 30s (default)
- **Time Range:** Last 24h (default)
- **Style:** Dark mode
- **Timezone:** UTC (default)

### Variables

**Vertical Variable** (available on main dashboard):
- Allows filtering by business vertical
- Default: All verticals
- Options: medical, beauty, food, fashion, realestate, etc. (64 total)

**Model Type Variable** (available on lifecycle dashboard):
- Allows filtering by model type
- Default: All model types
- Options: lightgbm, xgboost, etc.

### Annotations

Dashboards include automatic annotations for:
- **Model Version Updates**: Purple markers when models are updated
- **Significant Drift Events**: Red markers when high-severity drift is detected

---

## PromQL Queries Reference

For detailed PromQL queries and examples, see [PROMQL_QUERIES_GUIDE.md](PROMQL_QUERIES_GUIDE.md).

### Common Queries

**Drift by specific vertical:**
```promql
max by (feature) (catvrf_feature_drift_combined{vertical="medical"})
```

**Drifted features count (1h):**
```promql
sum(increase(catvrf_feature_drift_detected_total{severity="HIGH"}[1h]))
```

**Quota usage by vertical:**
```promql
catvrf_quota_usage_ratio{vertical=~"medical|beauty"}
```

---

## Troubleshooting

### No Data Showing

1. **Check Prometheus data source:** Ensure it's reachable
2. **Verify metrics endpoint:** Check `/metrics` is accessible
3. **Check metric names:** Ensure they match `catvrf_*` namespace
4. **Verify time range:** Adjust time range if data is old

### High Cardinality Issues

If you encounter high cardinality warnings:
- Use `label_values` with specific labels
- Avoid querying all features at once
- Use aggregation functions like `sum()`, `max()`, `avg()`

### Slow Queries

Optimize slow queries:
- Use shorter time ranges in `rate()` functions (e.g., `[5m]` instead of `[1h]`)
- Use `histogram_quantile` with fewer quantiles
- Add label filters to reduce data volume

### Variable Not Populating

If the vertical variable doesn't populate:
- Ensure metrics are being exported for your vertical
- Check that the `vertical` label exists in your metrics
- Verify Prometheus data source is correctly configured

---

## Performance Best Practices

1. **Use `rate()` for counters:** Always use `rate()` or `increase()` for counter metrics
2. **Limit time ranges:** Keep time ranges reasonable (e.g., `[5m]`, `[1h]`)
3. **Use label filters:** Filter by specific labels when possible
4. **Aggregate early:** Use `sum()`, `avg()`, `max()` to reduce data volume
5. **Avoid full table scans:** Don't query without label filters on high-cardinality metrics

---

## Vertical List

The Universal Vertical ML Monitor supports all 64 business verticals:

- Beauty
- Food
- RealEstate
- Fashion
- Travel
- Auto
- Hotels
- Medical
- Electronics
- Fitness
- Sports
- Luxury
- Insurance
- Legal
- Logistics
- Education
- CRM
- Delivery
- Payment
- Analytics
- Consulting
- Content
- Freelance
- EventPlanning
- Staff
- Inventory
- Taxi
- Tickets
- Wallet
- Pet
- WeddingPlanning
- Veterinary
- ToysAndGames
- Advertising
- CarRental
- Finances
- Flowers
- Furniture
- Pharmacy
- Photography
- ShortTermRentals
- SportsNutrition
- PersonalDevelopment
- HomeServices
- Gardening
- Geo
- GeoLogistics
- GroceryAndDelivery
- FarmDirect
- MeatShops
- OfficeCatering
- PartySupplies
- Confectionery
- ConstructionAndRepair
- CleaningServices
- Communication
- BooksAndLiterature
- Collectibles
- HobbyAndCraft
- HouseholdGoods
- Marketplace
- MusicAndInstruments
- VeganProducts
- Art

---

## Architecture & Design Principles

These dashboards follow CatVRF's production architecture principles:

- **Production & Scale first:** Designed for 5k-50k+ RPS
- **Clean Architecture:** Clear separation of concerns
- **Medical compliance:** All medical data anonymized (152-ФЗ, ФЗ-323)
- **AI in prod:** Async LLM calls with circuit breakers
- **Observability:** Full metrics integration with Prometheus
- **Alerting:** Multi-level alerting (Critical/Warning/Info)

---

## Contributing

When adding new dashboards:

1. Follow the naming convention: `catvrf-{purpose}.json`
2. Use the `catvrf` namespace for all metrics
3. Include alert thresholds in the dashboard description
4. Add documentation to this README
5. Update PROMQL_QUERIES_GUIDE.md with new queries
6. Test with sample data before deployment

---

## Support

**Project:** CatVRF  
**Version:** 2026.04.17  
**Documentation:** docs/grafana/  
**Issues:** GitHub Issues  
**Contact:** CatVRF Team

---

## License

MIT License - See project root for details.

---

**Last Updated:** 2026-04-17  
**Maintained by:** CatVRF Team
