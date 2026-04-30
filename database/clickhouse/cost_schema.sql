-- ClickHouse Cost Monitoring Schema for CatVRF Marketplace
-- Created: April 29, 2026
-- Purpose: FinOps — full cost transparency for Big Data vertical
-- Target: Cost ≤ 3–5% of GMV

-- ============================================================================
-- BILLING RAW TABLE (Cloud Billing API Import)
-- ============================================================================

DROP TABLE IF EXISTS ch_billing_raw;

CREATE TABLE ch_billing_raw (
    -- Identity
    billing_id String,
    cloud_provider Enum8('aws' = 1, 'gcp' = 2, 'azure' = 3, 'self_hosted' = 4),
    
    -- Time
    billing_date Date,
    billing_hour DateTime,
    imported_at DateTime DEFAULT now(),
    
    -- Cost Dimensions
    service String,           -- e.g. 'AmazonEC2', 'clickhouse_compute', 'kafka_broker'
    cost_category Enum8('compute' = 1, 'storage' = 2, 'network' = 3, 'license' = 4, 'support' = 5, 'other' = 6),
    environment Enum8('production' = 1, 'staging' = 2, 'development' = 3, 'test' = 4),
    
    -- Attribution
    tenant_id UInt32 DEFAULT 0,
    seller_id UInt32 DEFAULT 0,
    workload String DEFAULT '',  -- 'clv_training', 'abtest', 'seller_dashboard', 'ingestion', 'maintenance'
    resource_tags String,        -- JSON: {"seller_id":"123","vertical":"healthcare"}
    
    -- Cost
    cost_amount Float64,         -- USD
    cost_amount_rub Float64 DEFAULT 0,
    currency String DEFAULT 'USD',
    pricing_unit String DEFAULT '',  -- 'hours', 'GB-Month', 'requests', 'events'
    usage_quantity Float64 DEFAULT 0,
    unit_price Float64 DEFAULT 0,
    
    -- Billing Details
    account_id String DEFAULT '',
    subscription_id String DEFAULT '',
    invoice_id String DEFAULT '',
    payment_type Enum8('on_demand' = 1, 'reserved' = 2, 'spot' = 3, 'savings_plan' = 4),
    
    -- Optimization Flags
    is_optimizable UInt8 DEFAULT 0,
    optimization_potential Float64 DEFAULT 0,  -- estimated savings in USD
    
    -- Indices
    INDEX idx_provider_date (cloud_provider, billing_date) TYPE minmax GRANULARITY 8192,
    INDEX idx_service (service) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_category (cost_category) TYPE set(10) GRANULARITY 8192,
    INDEX idx_workload (workload) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_tenant (tenant_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_seller (seller_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_environment (environment) TYPE set(4) GRANULARITY 8192,
    
    -- Raw billing records from AWS Cost Explorer / GCP Billing / Azure Cost Management
) ENGINE = ReplacingMergeTree(imported_at)
PARTITION BY toYYYYMM(billing_date)
ORDER BY (cloud_provider, billing_date, service, cost_category)
TTL billing_date + INTERVAL 400 DAY
SETTINGS index_granularity = 8192;

-- ============================================================================
-- COST AGGREGATED DAILY TABLE (Materialized View Target)
-- ============================================================================

DROP TABLE IF EXISTS ch_cost_aggregated_daily;

CREATE TABLE ch_cost_aggregated_daily (
    -- Dimensions
    cost_date Date,
    cloud_provider Enum8('aws' = 1, 'gcp' = 2, 'azure' = 3, 'self_hosted' = 4),
    service String,
    cost_category Enum8('compute' = 1, 'storage' = 2, 'network' = 3, 'license' = 4, 'support' = 5, 'other' = 6),
    environment Enum8('production' = 1, 'staging' = 2, 'development' = 3, 'test' = 4),
    workload String DEFAULT '',
    tenant_id UInt32 DEFAULT 0,
    
    -- Aggregated Cost
    total_cost_usd Float64 DEFAULT 0,
    total_cost_rub Float64 DEFAULT 0,
    total_usage_quantity Float64 DEFAULT 0,
    avg_unit_price Float64 DEFAULT 0,
    
    -- Optimization
    total_optimizable_cost Float64 DEFAULT 0,
    total_optimization_potential Float64 DEFAULT 0,
    optimizable_ratio Float64 DEFAULT 0,  -- optimizable_cost / total_cost
    
    -- Budget Tracking
    monthly_budget_usd Float64 DEFAULT 0,
    budget_utilization_percent Float64 DEFAULT 0,
    
    -- Timestamp
    created_at DateTime DEFAULT now(),
    updated_at DateTime DEFAULT now(),
    
    -- Indices
    INDEX idx_date_category (cost_date, cost_category) TYPE minmax GRANULARITY 8192,
    INDEX idx_date_service (cost_date, service) TYPE minmax GRANULARITY 8192,
    INDEX idx_workload_date (workload, cost_date) TYPE minmax GRANULARITY 8192,
    
    -- Daily aggregated cost for dashboards and trend analysis
) ENGINE = ReplacingMergeTree(updated_at)
PARTITION BY toYYYYMM(cost_date)
ORDER BY (cost_date, cloud_provider, service, cost_category)
TTL cost_date + INTERVAL 730 DAY;

-- ============================================================================
-- COST ATTRIBUTION TABLE (Per Bounded Context / Seller)
-- ============================================================================

DROP TABLE IF EXISTS ch_cost_attribution;

CREATE TABLE ch_cost_attribution (
    -- Dimensions
    attribution_date Date,
    tenant_id UInt32,
    seller_id UInt32 DEFAULT 0,
    bounded_context Enum8(
        'ingestion' = 1,
        'clickhouse_storage' = 2,
        'clickhouse_compute' = 3,
        'kafka' = 4,
        'spark_ml' = 5,
        'clv_training' = 6,
        'clv_inference' = 7,
        'abtest' = 8,
        'seller_dashboard' = 9,
        'analytics_api' = 10,
        'feature_store' = 11,
        'monitoring' = 12,
        'other' = 13
    ),
    vertical String DEFAULT '',
    
    -- Attribution
    attributed_cost_usd Float64 DEFAULT 0,
    attributed_cost_rub Float64 DEFAULT 0,
    
    -- Unit Economics
    events_processed UInt64 DEFAULT 0,
    queries_executed UInt64 DEFAULT 0,
    dashboard_loads UInt64 DEFAULT 0,
    ml_predictions UInt64 DEFAULT 0,
    
    -- Unit Cost
    cost_per_1m_events Float64 DEFAULT 0,
    cost_per_query Float64 DEFAULT 0,
    cost_per_dashboard_load Float64 DEFAULT 0,
    cost_per_ml_prediction Float64 DEFAULT 0,
    cost_per_order_analyzed Float64 DEFAULT 0,
    
    -- ROI
    gmv_attributed Float64 DEFAULT 0,
    revenue_attributed Float64 DEFAULT 0,
    cost_to_gmv_ratio Float64 DEFAULT 0,  -- target: ≤ 0.03-0.05
    roi_multiplier Float64 DEFAULT 0,      -- revenue / cost
    
    -- Timestamp
    created_at DateTime DEFAULT now(),
    updated_at DateTime DEFAULT now(),
    
    -- Indices
    INDEX idx_tenant_date (tenant_id, attribution_date) TYPE minmax GRANULARITY 8192,
    INDEX idx_seller_date (seller_id, attribution_date) TYPE minmax GRANULARITY 8192,
    INDEX idx_context_date (bounded_context, attribution_date) TYPE minmax GRANULARITY 8192,
    INDEX idx_vertical (vertical) TYPE set(50) GRANULARITY 8192,
    
    -- Cost attribution per seller / bounded context for unit economics
) ENGINE = ReplacingMergeTree(updated_at)
PARTITION BY toYYYYMM(attribution_date)
ORDER BY (tenant_id, attribution_date, bounded_context, seller_id)
TTL attribution_date + INTERVAL 730 DAY;

-- ============================================================================
-- CLICKHOUSE INTERNAL COST METRICS TABLE
-- ============================================================================

DROP TABLE IF EXISTS ch_clickhouse_cost_metrics;

CREATE TABLE ch_clickhouse_cost_metrics (
    metric_date Date,
    metric_hour DateTime,
    
    -- Storage Metrics
    total_disk_bytes UInt64 DEFAULT 0,
    compressed_bytes UInt64 DEFAULT 0,
    uncompressed_bytes UInt64 DEFAULT 0,
    compression_ratio Float64 DEFAULT 0,
    parts_count UInt32 DEFAULT 0,
    active_parts_count UInt32 DEFAULT 0,
    
    -- Compute Metrics
    query_count UInt64 DEFAULT 0,
    insert_count UInt64 DEFAULT 0,
    merge_count UInt64 DEFAULT 0,
    mutation_count UInt64 DEFAULT 0,
    total_query_duration_ms Float64 DEFAULT 0,
    total_insert_duration_ms Float64 DEFAULT 0,
    total_merge_duration_ms Float64 DEFAULT 0,
    
    -- Query Cost Attribution (top queries)
    top_query_hashes String,      -- JSON: [{"hash":"xxx","cost_usd":1.2,"rows":5000}]
    top_seller_queries String,    -- JSON: [{"seller_id":123,"query_cost":5.0,"pct":0.4}]
    
    -- Tiered Storage
    hot_storage_bytes UInt64 DEFAULT 0,
    warm_storage_bytes UInt64 DEFAULT 0,
    cold_storage_bytes UInt64 DEFAULT 0,
    
    -- Estimated Cost
    estimated_storage_cost_usd Float64 DEFAULT 0,
    estimated_compute_cost_usd Float64 DEFAULT 0,
    estimated_total_cost_usd Float64 DEFAULT 0,
    
    -- Materialized View ROI
    mv_count UInt32 DEFAULT 0,
    mv_storage_bytes UInt64 DEFAULT 0,
    mv_savings_estimate_usd Float64 DEFAULT 0,  -- how much MVs save vs raw queries
    mv_roi Float64 DEFAULT 0,                    -- savings / storage_cost
    
    -- Timestamp
    created_at DateTime DEFAULT now(),
    
    -- Indices
    INDEX idx_date (metric_date) TYPE minmax GRANULARITY 8192,
    
    -- ClickHouse internal cost metrics for deep cost analysis
) ENGINE = ReplacingMergeTree(created_at)
PARTITION BY toYYYYMM(metric_date)
ORDER BY (metric_date, metric_hour)
TTL metric_date + INTERVAL 365 DAY;

-- ============================================================================
-- KAFKA COST METRICS TABLE
-- ============================================================================

DROP TABLE IF EXISTS ch_kafka_cost_metrics;

CREATE TABLE ch_kafka_cost_metrics (
    metric_date Date,
    metric_hour DateTime,
    
    -- Broker Metrics
    broker_count UInt32 DEFAULT 0,
    broker_hours Float64 DEFAULT 0,
    
    -- Throughput
    bytes_in UInt64 DEFAULT 0,
    bytes_out UInt64 DEFAULT 0,
    messages_in UInt64 DEFAULT 0,
    
    -- Storage / Retention
    log_size_bytes UInt64 DEFAULT 0,
    partition_count UInt32 DEFAULT 0,
    active_leader_partitions UInt32 DEFAULT 0,
    
    -- Cost
    broker_cost_usd Float64 DEFAULT 0,
    storage_cost_usd Float64 DEFAULT 0,
    network_cost_usd Float64 DEFAULT 0,
    total_cost_usd Float64 DEFAULT 0,
    
    -- Per-Topic Breakdown (top N)
    top_topics String,  -- JSON: [{"topic":"bigdata_events","bytes":1e9,"cost":5.0}]
    
    -- Timestamp
    created_at DateTime DEFAULT now(),
    
    -- Kafka cost metrics for broker/storage/network attribution
) ENGINE = ReplacingMergeTree(created_at)
PARTITION BY toYYYYMM(metric_date)
ORDER BY (metric_date, metric_hour)
TTL metric_date + INTERVAL 365 DAY;

-- ============================================================================
-- SPARK / ML COST METRICS TABLE
-- ============================================================================

DROP TABLE IF EXISTS ch_spark_ml_cost_metrics;

CREATE TABLE ch_spark_ml_cost_metrics (
    metric_date Date,
    job_name String,
    job_type Enum8('clv_training' = 1, 'clv_inference' = 2, 'abtest_analysis' = 3, 'rfm_segmentation' = 4, 'feature_engineering' = 5, 'data_export' = 6, 'maintenance' = 7),
    
    -- Job Metrics
    duration_seconds Float64 DEFAULT 0,
    cluster_size UInt32 DEFAULT 0,       -- number of executors
    cluster_memory_gb Float64 DEFAULT 0,
    instance_type String DEFAULT '',
    spot_instance UInt8 DEFAULT 0,
    
    -- Data Metrics
    input_rows UInt64 DEFAULT 0,
    output_rows UInt64 DEFAULT 0,
    shuffle_bytes UInt64 DEFAULT 0,
    
    -- Cost
    compute_cost_usd Float64 DEFAULT 0,
    storage_cost_usd Float64 DEFAULT 0,
    total_cost_usd Float64 DEFAULT 0,
    
    -- ROI (for ML jobs)
    gmv_uplift Float64 DEFAULT 0,
    revenue_uplift Float64 DEFAULT 0,
    roi_multiplier Float64 DEFAULT 0,
    
    -- Model Quality (for training jobs)
    model_accuracy Float64 DEFAULT 0,
    model_accuracy_baseline Float64 DEFAULT 0,
    
    -- Timestamp
    started_at DateTime DEFAULT now(),
    completed_at DateTime DEFAULT now(),
    created_at DateTime DEFAULT now(),
    
    -- Indices
    INDEX idx_date_type (metric_date, job_type) TYPE minmax GRANULARITY 8192,
    INDEX idx_job_name (job_name) TYPE bloom_filter GRANULARITY 8192,
    
    -- Spark/ML job cost metrics for ROI analysis
) ENGINE = ReplacingMergeTree(created_at)
PARTITION BY toYYYYMM(metric_date)
ORDER BY (metric_date, job_type, job_name)
TTL metric_date + INTERVAL 365 DAY;

-- ============================================================================
-- COST ANOMALY TABLE
-- ============================================================================

DROP TABLE IF EXISTS ch_cost_anomalies;

CREATE TABLE ch_cost_anomalies (
    anomaly_id UUID,
    detected_at DateTime DEFAULT now(),
    anomaly_date Date MATERIALIZED toDate(detected_at),
    
    -- Anomaly Details
    anomaly_type Enum8(
        'budget_exceeded' = 1,
        'cost_spike' = 2,
        'storage_growth' = 3,
        'query_cost_spike' = 4,
        'seller_overuse' = 5,
        'anomalous_pattern' = 6
    ),
    severity Enum8('low' = 1, 'medium' = 2, 'high' = 3, 'critical' = 4),
    
    -- Metrics
    expected_value Float64 DEFAULT 0,
    actual_value Float64 DEFAULT 0,
    deviation_percent Float64 DEFAULT 0,
    
    -- Context
    cloud_provider String DEFAULT '',
    service String DEFAULT '',
    seller_id UInt32 DEFAULT 0,
    workload String DEFAULT '',
    description String DEFAULT '',
    
    -- Resolution
    status Enum8('open' = 1, 'investigating' = 2, 'resolved' = 3, 'ignored' = 4),
    resolved_at Nullable(DateTime),
    resolution_note String DEFAULT '',
    
    -- Indices
    INDEX idx_date_type (anomaly_date, anomaly_type) TYPE minmax GRANULARITY 8192,
    INDEX idx_severity (severity) TYPE set(4) GRANULARITY 8192,
    INDEX idx_status (status) TYPE set(4) GRANULARITY 8192,
    INDEX idx_seller (seller_id) TYPE bloom_filter GRANULARITY 8192,
    
    -- Cost anomalies for alerting and FinOps review
) ENGINE = ReplacingMergeTree()
PARTITION BY toYYYYMM(anomaly_date)
ORDER BY (anomaly_date, anomaly_type, severity)
TTL anomaly_date + INTERVAL 180 DAY;

-- ============================================================================
-- OPTIMIZATION RECOMMENDATIONS TABLE
-- ============================================================================

DROP TABLE IF EXISTS ch_cost_optimization_recommendations;

CREATE TABLE ch_cost_optimization_recommendations (
    recommendation_id UUID,
    created_at DateTime DEFAULT now(),
    recommendation_date Date MATERIALIZED toDate(created_at),
    
    -- Recommendation
    optimization_type Enum8(
        'ttl_reduction' = 1,
        'compression_increase' = 2,
        'tiered_storage' = 3,
        'replication_decrease' = 4,
        'scheduled_merges' = 5,
        'kafka_retention' = 6,
        'spot_instances' = 7,
        'reserved_instances' = 8,
        'query_optimization' = 9,
        'mv_optimization' = 10,
        'auto_scale_down' = 11
    ),
    target_resource String,       -- table name, topic, cluster, etc.
    current_value String DEFAULT '',
    recommended_value String DEFAULT '',
    
    -- Impact
    estimated_savings_usd Float64 DEFAULT 0,
    estimated_savings_percent Float64 DEFAULT 0,
    risk_level Enum8('low' = 1, 'medium' = 2, 'high' = 3),
    
    -- Status
    status Enum8('pending' = 1, 'approved' = 2, 'applied' = 3, 'rejected' = 4, 'expired' = 5),
    applied_at Nullable(DateTime),
    actual_savings_usd Float64 DEFAULT 0,
    
    -- Context
    rationale String DEFAULT '',
    sql_command String DEFAULT '',  -- e.g. ALTER TABLE ... TTL ...
    
    -- Indices
    INDEX idx_date_type (recommendation_date, optimization_type) TYPE minmax GRANULARITY 8192,
    INDEX idx_status (status) TYPE set(5) GRANULARITY 8192,
    INDEX idx_savings (estimated_savings_usd) TYPE minmax GRANULARITY 8192,
    
    -- Optimization recommendations from auto-analysis
) ENGINE = ReplacingMergeTree()
PARTITION BY toYYYYMM(recommendation_date)
ORDER BY (recommendation_date, optimization_type, estimated_savings_usd DESC)
TTL recommendation_date + INTERVAL 180 DAY;

-- ============================================================================
-- MATERIALIALIZED VIEWS
-- ============================================================================

-- Daily cost aggregation from billing_raw
DROP VIEW IF EXISTS ch_cost_aggregated_daily_mv;

CREATE MATERIALIZED VIEW ch_cost_aggregated_daily_mv TO ch_cost_aggregated_daily AS
SELECT
    billing_date AS cost_date,
    cloud_provider,
    service,
    cost_category,
    environment,
    workload,
    tenant_id,
    
    sum(cost_amount) AS total_cost_usd,
    sum(cost_amount_rub) AS total_cost_rub,
    sum(usage_quantity) AS total_usage_quantity,
    if(sum(usage_quantity) > 0, sum(cost_amount) / sum(usage_quantity), 0) AS avg_unit_price,
    
    sumIf(cost_amount, is_optimizable = 1) AS total_optimizable_cost,
    sum(optimization_potential) AS total_optimization_potential,
    if(sum(cost_amount) > 0, sumIf(cost_amount, is_optimizable = 1) / sum(cost_amount), 0) AS optimizable_ratio,
    
    0 AS monthly_budget_usd,
    0 AS budget_utilization_percent,
    
    now() AS created_at,
    now() AS updated_at
FROM ch_billing_raw
GROUP BY billing_date, cloud_provider, service, cost_category, environment, workload, tenant_id;

-- ============================================================================
-- QUERIES FOR FINOPS DASHBOARDS
-- ============================================================================

-- Daily total cost with breakdown
-- SELECT cost_date, cloud_provider, cost_category, sum(total_cost_usd)
-- FROM ch_cost_aggregated_daily
-- WHERE cost_date >= today() - 30
-- GROUP BY cost_date, cloud_provider, cost_category
-- ORDER BY cost_date;

-- Seller attribution (who costs the most)
-- SELECT seller_id, sum(attributed_cost_usd) AS total_cost, sum(gmv_attributed) AS gmv,
--        sum(attributed_cost_usd) / nullIf(sum(gmv_attributed), 0) AS cost_to_gmv
-- FROM ch_cost_attribution
-- WHERE attribution_date >= today() - 30
-- GROUP BY seller_id
-- ORDER BY total_cost DESC
-- LIMIT 20;

-- Unit economics
-- SELECT attribution_date, bounded_context,
--        sum(attributed_cost_usd) AS cost,
--        sum(events_processed) AS events,
--        sum(queries_executed) AS queries,
--        sum(ml_predictions) AS predictions,
--        if(sum(events_processed) > 0, sum(attributed_cost_usd) * 1e6 / sum(events_processed), 0) AS cost_per_1m_events,
--        if(sum(queries_executed) > 0, sum(attributed_cost_usd) / sum(queries_executed), 0) AS cost_per_query,
--        if(sum(ml_predictions) > 0, sum(attributed_cost_usd) / sum(ml_predictions), 0) AS cost_per_prediction
-- FROM ch_cost_attribution
-- WHERE attribution_date >= today() - 30
-- GROUP BY attribution_date, bounded_context;

-- ClickHouse compression ratio
-- SELECT metric_date, compression_ratio, estimated_storage_cost_usd, estimated_compute_cost_usd
-- FROM ch_clickhouse_cost_metrics
-- WHERE metric_date >= today() - 30
-- ORDER BY metric_date;

-- CLV training ROI
-- SELECT metric_date, job_name, total_cost_usd, gmv_uplift, roi_multiplier
-- FROM ch_spark_ml_cost_metrics
-- WHERE job_type = 'clv_training' AND metric_date >= today() - 90
-- ORDER BY metric_date;

SELECT 'ClickHouse Cost Schema Installation Complete' AS status,
       now() AS created_at,
       'Tables: billing_raw, cost_aggregated_daily, cost_attribution, clickhouse_cost_metrics, kafka_cost_metrics, spark_ml_cost_metrics, cost_anomalies, optimization_recommendations' AS components;
