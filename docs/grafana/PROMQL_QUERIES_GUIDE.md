# CatVRF Grafana PromQL Queries Guide

**Version:** 2026.04.17  
**Project:** CatVRF - AI-powered Healthcare Marketplace  
**Purpose:** Production-ready PromQL queries for Grafana dashboards monitoring ML drift, quota usage, and fraud detection.

---

## Table of Contents

1. [Available Metrics](#available-metrics)
2. [Feature Drift Detection Queries](#feature-drift-detection-queries)
3. [ML Retrain & Model Lifecycle Queries](#ml-retrain--model-lifecycle-queries)
4. [Quota & AI Consumption Queries](#quota--ai-consumption-queries)
5. [Fraud Detection Queries](#fraud-detection-queries)
6. [Medical Vertical Specific Queries](#medical-vertical-specific-queries)
7. [Alert Thresholds](#alert-thresholds)
8. [Dashboard Import Instructions](#dashboard-import-instructions)

---

## Available Metrics

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

## Feature Drift Detection Queries

### PSI (Population Stability Index)

**Get PSI for a specific feature and vertical:**
```promql
catvrf_feature_drift_psi{feature="ai_diagnosis_frequency", vertical="medical"}
```

**Max PSI across all features in a vertical:**
```promql
max by (vertical) (catvrf_feature_drift_psi{vertical="medical"})
```

**PSI heatmap for all verticals:**
```promql
catvrf_feature_drift_psi
```

**PSI trend over time:**
```promql
catvrf_feature_drift_psi{feature="ai_diagnosis_frequency", vertical="medical"}
```

### KS Test (Kolmogorov-Smirnov)

**Get KS p-value for a feature:**
```promql
catvrf_feature_drift_ks{feature="health_score", vertical="medical"}
```

**Features with significant KS drift (p-value ≤ 0.05):**
```promql
catvrf_feature_drift_ks <= 0.05
```

### JS Divergence

**Get JS divergence for a feature:**
```promql
catvrf_feature_drift_js{feature="emergency_event_rate", vertical="medical"}
```

### Combined Drift Score

**Get combined drift score (PSI + KS + JS weighted):**
```promql
catvrf_feature_drift_combined{feature="ai_diagnosis_frequency", vertical="medical"}
```

**Max combined drift score across all verticals:**
```promql
max(catvrf_feature_drift_combined)
```

**Drift events by severity:**
```promql
sum(increase(catvrf_feature_drift_detected_total[1h])) by (severity)
```

**High severity drift events:**
```promql
sum(increase(catvrf_feature_drift_detected_total{severity="HIGH"}[1h])) by (vertical, feature)
```

### Vertical-Level Drift

**Vertical drift score:**
```promql
catvrf_vertical_drift_score{vertical="medical"}
```

**Verticals with high drift:**
```promql
catvrf_vertical_drift_score > 0.7
```

**Vertical drift events:**
```promql
sum(increase(catvrf_vertical_drift_detected_total[24h])) by (vertical)
```

---

## ML Retrain & Model Lifecycle Queries

### Retrain Duration

**p95 retrain duration:**
```promql
histogram_quantile(0.95, rate(catvrf_ml_retrain_duration_seconds_bucket[5m]))
```

**p50 retrain duration:**
```promql
histogram_quantile(0.50, rate(catvrf_ml_retrain_duration_seconds_bucket[5m]))
```

**Retrain duration over time:**
```promql
histogram_quantile(0.95, rate(catvrf_ml_retrain_duration_seconds_bucket[5m]))
```

### Retrain Success Rate

**Retrain success rate:**
```promql
rate(catvrf_ml_retrain_success_total{status="completed"}[1h]) / rate(catvrf_ml_retrain_success_total[1h])
```

**Retrain status count:**
```promql
sum(increase(catvrf_ml_retrain_success_total[1h])) by (status)
```

### Tenant Processing

**Total tenants processed:**
```promql
catvrf_ml_retrain_tenants_processed_total
```

**Tenants processed rate:**
```promql
rate(catvrf_ml_retrain_tenants_processed_total[5m])
```

### Model Version

**Current active model AUC:**
```promql
catvrf_ml_model_auc_current
```

**Model promotion timestamp:**
```promql
catvrf_ml_model_promoted_timestamp
```

**Model version update events:**
```promql
sum(increase(catvrf_ml_model_version_updated_total[24h])) by (action, model_type)
```

**Model promotions (24h):**
```promql
sum(increase(catvrf_ml_model_version_updated_total{action="promoted"}[24h])) by (model_type)
```

**Model rollbacks (24h):**
```promql
sum(increase(catvrf_ml_model_version_updated_total{action="rolled_back"}[24h])) by (model_type)
```

### Training Metrics

**Accuracy, Precision, Recall, F1:**
```promql
catvrf_ml_model_training_metric{metric_name=~"accuracy|precision|recall|f1_score"}
```

**Training duration:**
```promql
catvrf_ml_model_training_metric{metric_name="training_duration_seconds"}
```

**AUC over time:**
```promql
catvrf_ml_model_auc_current
```

---

## Quota & AI Consumption Queries

### Quota Usage

**Quota usage ratio by vertical:**
```promql
catvrf_quota_usage_ratio{vertical="medical"}
```

**Quota usage ratio by resource type:**
```promql
catvrf_quota_usage_ratio{resource_type="ai_tokens"}
```

**Verticals with high quota usage (>85%):**
```promql
catvrf_quota_usage_ratio > 0.85
```

**Quota usage heatmap:**
```promql
catvrf_quota_usage_ratio
```

### Quota Exceeded Events

**Quota exceeded events (1h):**
```promql
sum(increase(catvrf_quota_exceeded_total[1h])) by (vertical, resource_type)
```

**Quota exceeded by vertical:**
```promql
sum(increase(catvrf_quota_exceeded_total[24h])) by (vertical)
```

### AI Tokens Consumption

**AI tokens consumed (24h) by vertical:**
```promql
sum(increase(catvrf_ai_tokens_consumed_total[24h])) by (vertical)
```

**AI tokens consumed (24h) by model:**
```promql
sum(increase(catvrf_ai_tokens_consumed_total[24h])) by (model, vertical)
```

**AI tokens burn rate (1h):**
```promql
sum(increase(catvrf_ai_tokens_consumed_total[1h])) by (model, vertical)
```

**Total AI tokens consumed (24h):**
```promql
sum(increase(catvrf_ai_tokens_consumed_total[24h]))
```

---

## Fraud Detection Queries

### Inference Latency

**p95 inference latency:**
```promql
histogram_quantile(0.95, rate(catvrf_fraud_ml_inference_latency_seconds_bucket[5m]))
```

**p50 inference latency:**
```promql
histogram_quantile(0.50, rate(catvrf_fraud_ml_inference_latency_seconds_bucket[5m]))
```

**Inference latency by model version:**
```promql
histogram_quantile(0.95, rate(catvrf_fraud_ml_inference_latency_seconds_bucket[5m])) by (model_version)
```

### Fraud Score Distribution

**p95 fraud score:**
```promql
histogram_quantile(0.95, rate(catvrf_fraud_score_distribution_bucket[5m]))
```

**p50 fraud score:**
```promql
histogram_quantile(0.50, rate(catvrf_fraud_score_distribution_bucket[5m]))
```

**Fraud score by vertical:**
```promql
histogram_quantile(0.95, rate(catvrf_fraud_score_distribution_bucket{vertical="medical"}[5m]))
```

### Fraud Blocking

**Fraud blocked by ML (1h):**
```promql
sum(increase(catvrf_fraud_blocked_by_ml_total[1h])) by (vertical, reason)
```

**Fraud blocked by reason:**
```promql
sum(increase(catvrf_fraud_blocked_by_ml_total[24h])) by (reason)
```

**Total fraud blocked (24h):**
```promql
sum(increase(catvrf_fraud_blocked_by_ml_total[24h]))
```

---

## Medical Vertical Specific Queries

### AI Diagnosis Frequency Drift

**PSI for AI diagnosis frequency:**
```promql
catvrf_feature_drift_psi{vertical="medical", feature="ai_diagnosis_frequency"}
```

**KS p-value for AI diagnosis frequency:**
```promql
catvrf_feature_drift_ks{vertical="medical", feature="ai_diagnosis_frequency"}
```

**Combined drift for AI diagnosis:**
```promql
catvrf_feature_drift_combined{vertical="medical", feature="ai_diagnosis_frequency"}
```

### Health Score Drift

**PSI for health score:**
```promql
catvrf_feature_drift_psi{vertical="medical", feature="health_score"}
```

**KS p-value for health score:**
```promql
catvrf_feature_drift_ks{vertical="medical", feature="health_score"}
```

**Combined drift for health score:**
```promql
catvrf_feature_drift_combined{vertical="medical", feature="health_score"}
```

### Emergency Event Rate Drift

**PSI for emergency event rate:**
```promql
catvrf_feature_drift_psi{vertical="medical", feature="emergency_event_rate"}
```

### Medical Quota & AI Usage

**Medical quota usage:**
```promql
catvrf_quota_usage_ratio{vertical="medical"}
```

**Medical AI tokens consumed (24h):**
```promql
sum(increase(catvrf_ai_tokens_consumed_total{vertical="medical"}[24h])) by (model)
```

**Medical quota burn rate by operation:**
```promql
sum(increase(catvrf_ai_tokens_consumed_total{vertical="medical"}[1h])) by (model)
```

### Medical Fraud Detection

**Fraud score distribution (medical):**
```promql
histogram_quantile(0.95, rate(catvrf_fraud_score_distribution_bucket{vertical="medical"}[5m]))
```

**Fraud blocked by ML (medical):**
```promql
sum(increase(catvrf_fraud_blocked_by_ml_total{vertical="medical"}[1h])) by (reason)
```

---

## Alert Thresholds

### Critical Alerts (PagerDuty/Slack)

**High feature drift (PSI > 0.25):**
```promql
catvrf_feature_drift_psi > 0.25
```

**Significant KS drift (p-value ≤ 0.05):**
```promql
catvrf_feature_drift_ks <= 0.05
```

**High combined drift score (> 0.7):**
```promql
catvrf_feature_drift_combined > 0.7
```

**Quota exceeded (> 95%):**
```promql
catvrf_quota_usage_ratio > 0.95
```

**Model AUC degradation (< 0.90):**
```promql
catvrf_ml_model_auc_current < 0.90
```

**Retrain timeout (> 30 min):**
```promql
histogram_quantile(0.95, rate(catvrf_ml_retrain_duration_seconds_bucket[5m])) > 1800
```

### Warning Alerts (Email/Slack)

**Moderate feature drift (PSI > 0.1):**
```promql
catvrf_feature_drift_psi > 0.1
```

**High quota usage (> 85%):**
```promql
catvrf_quota_usage_ratio > 0.85
```

**Model AUC warning (< 0.92):**
```promql
catvrf_ml_model_auc_current < 0.92
```

**Retrain duration warning (> 15 min):**
```promql
histogram_quantile(0.95, rate(catvrf_ml_retrain_duration_seconds_bucket[5m])) > 900
```

### Info Alerts (Slack only)

**Model version updated:**
```promql
increase(catvrf_ml_model_version_updated_total[5m]) > 0
```

**Significant drift event:**
```promql
increase(catvrf_feature_drift_detected_total{severity="HIGH"}[5m]) > 0
```

---

## Dashboard Import Instructions

### Prerequisites

1. **Prometheus Data Source** configured in Grafana
2. **Spatie Laravel Prometheus** installed and configured in CatVRF
3. **Prometheus exporter** running on `/metrics` endpoint

### Import Dashboard

1. Navigate to Grafana → Dashboards → New → Import
2. Choose one of the following methods:
   - **Upload JSON file**: Select the JSON file from `docs/grafana/`
   - **Paste JSON**: Copy and paste the JSON content
   - **Import by ID**: (if published to Grafana.com)

3. Configure the data source:
   - Select your Prometheus data source
   - Update the variable `DS_PROMETHEUS` if needed

4. Click **Import**

### Available Dashboards

| Dashboard | JSON File | UID | Description |
|-----------|-----------|-----|-------------|
| CatVRF ML Drift & Quota Monitor | `catvrf-ml-drift-quota-monitor.json` | `catvrf-ml-drift-quota-monitor` | Main dashboard for ML drift, quota, and fraud monitoring |
| Medical Healthcare ML Monitor | `medical-healthcare-ml-monitor.json` | `catvrf-medical-healthcare-ml-monitor` | Specialized dashboard for Medical vertical |
| ML Retrain & Model Lifecycle | `ml-retrain-model-lifecycle.json` | `catvrf-ml-retrain-lifecycle` | Model retrain and lifecycle monitoring |

### Dashboard Configuration

**Refresh Interval:** 30s (default)  
**Time Range:** Last 24h (default)  
**Style:** Dark mode  

### Annotations

Dashboards include automatic annotations for:
- **Model Version Updates**: Purple markers when models are updated
- **Significant Drift Events**: Red markers when high-severity drift is detected

### Variables

**Vertical Variable** (available on main dashboard):
- Allows filtering by business vertical
- Default: All verticals
- Options: medical, beauty, food, etc.

**Model Type Variable** (available on lifecycle dashboard):
- Allows filtering by model type
- Default: All model types
- Options: lightgbm, xgboost, etc.

---

## Advanced Queries

### Feature Drift Heatmap (All 127 Verticals)

```promql
catvrf_feature_drift_combined
```

**Visualization:** Heatmap panel  
**X-axis:** Feature  
**Y-axis:** Vertical  
**Color:** Drift score (green < 0.1, yellow 0.1-0.25, red > 0.25)

### Top 10 Drifted Features

```promql
topk(10, catvrf_feature_drift_detected_total)
```

**Visualization:** Table panel  
**Columns:** Feature, Vertical, Severity

### Quota Usage Heatmap (All Verticals)

```promql
catvrf_quota_usage_ratio
```

**Visualization:** Heatmap panel  
**X-axis:** Resource Type  
**Y-axis:** Vertical  
**Color:** Usage ratio (green < 0.7, yellow 0.7-0.85, red > 0.85)

### Fraud Block Rate vs Quota Abuse

```promql
# Fraud blocked
sum(increase(catvrf_fraud_blocked_by_ml_total[1h])) by (vertical)

# Quota exceeded
sum(increase(catvrf_quota_exceeded_total[1h])) by (vertical)
```

**Visualization:** Multi-line time series

### Shadow vs Active Model Comparison

```promql
catvrf_ml_model_auc_current
```

**Visualization:** Multi-line time series  
**Annotations:** Model promotion events

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

---

## Performance Best Practices

1. **Use `rate()` for counters:** Always use `rate()` or `increase()` for counter metrics
2. **Limit time ranges:** Keep time ranges reasonable (e.g., `[5m]`, `[1h]`)
3. **Use label filters:** Filter by specific labels when possible
4. **Aggregate early:** Use `sum()`, `avg()`, `max()` to reduce data volume
5. **Avoid full table scans:** Don't query without label filters on high-cardinality metrics

---

## Contact & Support

**Project:** CatVRF  
**Version:** 2026.04.17  
**Documentation:** docs/grafana/  
**Issues:** GitHub Issues  

---

**Last Updated:** 2026-04-17  
**Author:** CatVRF Team  
**License:** MIT
