-- ClickHouse Schema for ML Model Drift Monitoring
-- CANON 2026 - Production Ready
-- 
-- This schema supports comprehensive drift monitoring for ML models:
-- - Data Drift (Feature Drift): Changes in input feature distributions
-- - Concept Drift (Label Drift): Changes in feature -> target relationship
-- - Model Drift (Prediction Drift): Changes in prediction distribution
--
-- Compliance: 152-ФЗ, ФЗ-323 (anonymized data, audit trail)

-- Drop existing tables if they exist (for development)
DROP TABLE IF EXISTS fraud_drift_reports;
DROP TABLE IF EXISTS fraud_drift_metrics;
DROP TABLE IF EXISTS fraud_model_performance;
DROP TABLE IF EXISTS fraud_prediction_distribution;

-- Main drift reports table (daily analysis results)
CREATE TABLE IF NOT EXISTS fraud_drift_reports (
    report_id UUID DEFAULT generateUUIDv4(),
    model_type String,
    vertical_code String DEFAULT 'default',
    correlation_id String,
    status String, -- 'success', 'error', 'insufficient_data'
    
    -- Data drift summary
    data_drift_overall_detected Bool,
    data_drift_max_score Float64,
    data_drift_drifted_features_count UInt32,
    
    -- Concept drift summary
    concept_drift_detected Bool,
    concept_drift_accuracy_decay Float64,
    concept_drift_f1_decay Float64,
    concept_drift_is_critical Bool,
    
    -- Prediction drift summary
    prediction_drift_detected Bool,
    prediction_drift_score Float64,
    prediction_drift_psi Float64,
    
    -- Full report data (JSON)
    report_data String, -- JSON with full details
    
    -- Metadata
    timestamp DateTime DEFAULT now(),
    created_at DateTime DEFAULT now(),
    
    -- Partitioning for efficient queries
    -- Partition by model type and date for efficient time-series queries
    -- Using MergeTree engine for high-performance analytics
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(timestamp)
ORDER BY (model_type, vertical_code, timestamp)
TTL timestamp + INTERVAL 365 DAY -- Retain for 1 year (compliance)
SETTINGS index_granularity = 8192;

-- Individual drift metrics table (for feature-level tracking)
CREATE TABLE IF NOT EXISTS fraud_drift_metrics (
    metric_id UUID DEFAULT generateUUIDv4(),
    model_type String,
    vertical_code String DEFAULT 'default',
    feature_name String,
    metric_type String, -- 'psi', 'ks', 'js', 'combined'
    metric_value Float64,
    threshold Float64,
    severity String, -- 'LOW', 'MEDIUM', 'HIGH'
    drift_detected Bool,
    
    -- Detailed metrics
    psi_value Nullable(Float64),
    ks_statistic Nullable(Float64),
    ks_p_value Nullable(Float64),
    js_divergence Nullable(Float64),
    js_distance Nullable(Float64),
    
    -- SHAP explanation (if drift detected)
    shap_contribution Nullable(Float64),
    shap_top_features Nullable(String), -- JSON array
    
    -- Metadata
    correlation_id String,
    timestamp DateTime DEFAULT now(),
    created_at DateTime DEFAULT now(),
    
    -- Partitioning
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(timestamp)
ORDER BY (model_type, vertical_code, feature_name, metric_type, timestamp)
TTL timestamp + INTERVAL 365 DAY
SETTINGS index_granularity = 8192;

-- Model performance metrics table (for concept drift tracking)
CREATE TABLE IF NOT EXISTS fraud_model_performance (
    performance_id UUID DEFAULT generateUUIDv4(),
    model_version String,
    model_type String,
    vertical_code String DEFAULT 'default',
    
    -- Performance metrics
    accuracy Float64,
    precision Float64,
    recall Float64,
    f1_score Float64,
    auc_roc Float64,
    
    -- Sample information
    sample_count UInt32,
    
    -- Decay metrics (compared to baseline)
    accuracy_decay Nullable(Float64),
    f1_decay Nullable(Float64),
    
    -- Metadata
    timestamp DateTime DEFAULT now(),
    created_at DateTime DEFAULT now(),
    
    -- Partitioning
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(timestamp)
ORDER BY (model_type, model_version, vertical_code, timestamp)
TTL timestamp + INTERVAL 365 DAY
SETTINGS index_granularity = 8192;

-- Prediction distribution table (for model drift tracking)
CREATE TABLE IF NOT EXISTS fraud_prediction_distribution (
    distribution_id UUID DEFAULT generateUUIDv4(),
    model_type String,
    vertical_code String DEFAULT 'default',
    model_version String,
    
    -- Prediction statistics
    prediction_value Float64,
    prediction_count UInt32,
    
    -- Distribution metrics
    mean Float64,
    std_dev Float64,
    min_value Float64,
    max_value Float64,
    p25 Float64,
    p50 Float64,
    p75 Float64,
    
    -- Window information
    window_start DateTime,
    window_end DateTime,
    
    -- Metadata
    timestamp DateTime DEFAULT now(),
    created_at DateTime DEFAULT now(),
    
    -- Partitioning
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(timestamp)
ORDER BY (model_type, vertical_code, model_version, timestamp)
TTL timestamp + INTERVAL 365 DAY
SETTINGS index_granularity = 8192;

-- Materialized view for real-time drift monitoring dashboard
-- Aggregates drift metrics by hour for faster dashboard queries
CREATE MATERIALIZED VIEW IF NOT EXISTS fraud_drift_metrics_hourly_mv
ENGINE = SummingMergeTree()
PARTITION BY toYYYYMM(hour_timestamp)
ORDER BY (model_type, vertical_code, hour_timestamp)
AS SELECT
    toStartOfHour(timestamp) AS hour_timestamp,
    model_type,
    vertical_code,
    metric_type,
    severity,
    count() AS metric_count,
    avg(metric_value) AS avg_metric_value,
    max(metric_value) AS max_metric_value,
    countIf(drift_detected) AS drift_detected_count
FROM fraud_drift_metrics
GROUP BY hour_timestamp, model_type, vertical_code, metric_type, severity;

-- Materialized view for daily drift summary
-- Aggregates drift reports by day for trend analysis
CREATE MATERIALIZED VIEW IF NOT EXISTS fraud_drift_daily_summary_mv
ENGINE = SummingMergeTree()
PARTITION BY toYYYYMM(day_timestamp)
ORDER BY (model_type, vertical_code, day_timestamp)
AS SELECT
    toDate(timestamp) AS day_timestamp,
    model_type,
    vertical_code,
    count() AS report_count,
    countIf(data_drift_overall_detected) AS data_drift_count,
    countIf(concept_drift_detected) AS concept_drift_count,
    countIf(prediction_drift_detected) AS prediction_drift_count,
    avg(data_drift_max_score) AS avg_drift_score,
    avg(concept_drift_accuracy_decay) AS avg_accuracy_decay
FROM fraud_drift_reports
WHERE status = 'success'
GROUP BY day_timestamp, model_type, vertical_code;

-- Create indexes for common queries
-- Note: ClickHouse doesn't support traditional indexes like MySQL/PostgreSQL
-- Instead, it uses data skipping indices

-- Create data skipping index for model_type
-- ALTER TABLE fraud_drift_reports ADD INDEX idx_model_type model_type TYPE bloom_filter GRANULARITY 1;

-- Create data skipping index for vertical_code
-- ALTER TABLE fraud_drift_reports ADD INDEX idx_vertical_code vertical_code TYPE bloom_filter GRANULARITY 1;

-- Create data skipping index for timestamp range queries
-- ALTER TABLE fraud_drift_reports ADD INDEX idx_timestamp timestamp TYPE minmax GRANULARITY 1;

-- Sample queries for monitoring dashboard:

-- Get latest drift report for a model
-- SELECT * FROM fraud_drift_reports 
-- WHERE model_type = 'fraud_ml_ensemble' 
-- AND vertical_code = 'medical' 
-- ORDER BY timestamp DESC 
-- LIMIT 1;

-- Get drift trend over last 30 days
-- SELECT day_timestamp, avg_drift_score, data_drift_count, concept_drift_count
-- FROM fraud_drift_daily_summary_mv
-- WHERE model_type = 'fraud_ml_ensemble'
-- AND vertical_code = 'medical'
-- AND day_timestamp >= now() - INTERVAL 30 DAY
-- ORDER BY day_timestamp DESC;

-- Get features with highest drift
-- SELECT feature_name, metric_type, avg(metric_value) as avg_drift, countIf(drift_detected) as drift_count
-- FROM fraud_drift_metrics
-- WHERE model_type = 'fraud_ml_ensemble'
-- AND vertical_code = 'medical'
-- AND timestamp >= now() - INTERVAL 7 DAY
-- GROUP BY feature_name, metric_type
-- ORDER BY avg_drift DESC
-- LIMIT 10;

-- Get performance metrics trend
-- SELECT model_version, timestamp, accuracy, f1_score, accuracy_decay
-- FROM fraud_model_performance
-- WHERE model_type = 'fraud_ml_ensemble'
-- AND vertical_code = 'medical'
-- ORDER BY timestamp DESC
-- LIMIT 30;
