-- ClickHouse Big Data Schema for CatVRF Marketplace
-- Created: April 28, 2026
-- Scale: 50M+ events/day, 500M+ historical records

-- ============================================================================
-- RAW EVENTS TABLE (Speed Layer - Kafka Ingestion)
-- ============================================================================

DROP TABLE IF EXISTS ch_raw_events;

CREATE TABLE ch_raw_events (
    -- Identifiers
    event_id UUID,
    event_type String,
    event_category String,
    tenant_id UInt32,
    
    -- Entity IDs
    user_id Nullable(UInt32),
    seller_id Nullable(UInt32),
    product_id Nullable(UInt32),
    order_id Nullable(UInt32),
    session_id Nullable(String),
    vertical Nullable(String),
    
    -- Event Data
    properties String,  -- JSON stored as String for flexibility
    monetary_value Nullable(Float64),
    
    -- Context
    context String,  -- JSON: user_agent, app_version, environment, etc.
    correlation_id String,
    
    -- User Info (for enrichment)
    user_agent Nullable(String),
    ip_address Nullable(String),
    device_type Nullable(String),
    
    -- Timestamps
    created_at DateTime,
    event_date Date MATERIALIZED toDate(created_at),
    event_hour DateTime MATERIALIZED toStartOfHour(created_at),
    
    -- PII Flags
    pii_sensitive UInt8 MATERIALIZED 0,
    enriched_clv UInt8 MATERIALIZED 0,
    
    -- CLV Enrichment (filled by enrichment job)
    clv_segment Nullable(String),
    clv_score Nullable(Float32),
    rfm_segment Nullable(String),
    
    -- Indices for performance
    INDEX idx_tenant_created (tenant_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_event_type (event_type) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_user_id (user_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_seller_id (seller_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_order_id (order_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_correlation (correlation_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_vertical (vertical) TYPE set(50) GRANULARITY 8192,
    
    -- Raw events ingested from Kafka - 50M+ events/day
) ENGINE = ReplacingMergeTree(created_at)
PARTITION BY toYYYYMMDD(created_at)
ORDER BY (tenant_id, created_at, event_type)
TTL created_at + INTERVAL 90 DAY
SETTINGS index_granularity = 8192;

-- ============================================================================
-- DAILY METRICS TABLE (Batch Layer - Aggregates)
-- ============================================================================

DROP TABLE IF EXISTS ch_daily_metrics;

CREATE TABLE ch_daily_metrics (
    -- Dimensions
    tenant_id UInt32,
    metric_date Date,
    vertical String DEFAULT '',
    seller_id UInt32 DEFAULT 0,
    product_id UInt32 DEFAULT 0,
    
    -- Order Metrics
    orders_total UInt64 DEFAULT 0,
    orders_completed UInt64 DEFAULT 0,
    orders_cancelled UInt64 DEFAULT 0,
    gmv_total Float64 DEFAULT 0,
    gmv_completed Float64 DEFAULT 0,
    aov_avg Float64 DEFAULT 0,
    
    -- User Metrics
    users_new UInt64 DEFAULT 0,
    users_active UInt64 DEFAULT 0,
    users_returning UInt64 DEFAULT 0,
    sessions_total UInt64 DEFAULT 0,
    
    -- Product Metrics
    product_views UInt64 DEFAULT 0,
    product_adds_to_cart UInt64 DEFAULT 0,
    product_purchases UInt64 DEFAULT 0,
    
    -- Conversion Metrics
    conversion_rate_funnel Float64 DEFAULT 0,
    conversion_rate_cart_to_order Float64 DEFAULT 0,
    
    -- Revenue Metrics
    revenue_total Float64 DEFAULT 0,
    revenue_refunded Float64 DEFAULT 0,
    
    -- Timestamp
    created_at DateTime DEFAULT now(),
    
    -- Indices
    INDEX idx_tenant_date (tenant_id, metric_date) TYPE minmax GRANULARITY 8192,
    INDEX idx_seller_date (seller_id, metric_date) TYPE minmax GRANULARITY 8192,
    INDEX idx_vertical (vertical) TYPE set(50) GRANULARITY 8192,
    
    -- Daily aggregated metrics for dashboards
) ENGINE = ReplacingMergeTree(created_at)
PARTITION BY toYYYYMM(metric_date)
ORDER BY (tenant_id, metric_date)
TTL metric_date + INTERVAL 730 DAY;

-- ============================================================================
-- SELLER DAILY METRICS TABLE (Seller Analytics)
-- ============================================================================

DROP TABLE IF EXISTS ch_seller_daily_metrics;

CREATE TABLE ch_seller_daily_metrics (
    -- Dimensions
    tenant_id UInt32,
    seller_id UInt32,
    metric_date Date,
    
    -- Sales Metrics
    orders_count UInt64,
    orders_completed UInt64,
    orders_cancelled UInt64,
    gmv_total Float64,
    revenue_total Float64,
    commission_total Float64,
    
    -- Product Metrics
    products_active UInt16,
    products_sold UInt64,
    top_product_id Nullable(UInt32),
    top_product_sales UInt64,
    
    -- Customer Metrics
    customers_unique UInt32,
    customers_new UInt32,
    customers_returning UInt32,
    
    -- Performance Metrics
    avg_order_value Float64,
    avg_items_per_order Float32,
    fulfillment_rate Float32,
    cancellation_rate Float32,
    refund_rate Float32,
    
    -- Rating Metrics
    avg_rating Float32,
    review_count UInt32,
    
    -- Inventory Metrics
    inventory_turnover Float32,
    stockout_count UInt32,
    
    -- CLV Metrics
    customer_ltv_avg Float64,
    customer_ltv_total Float64,
    
    -- Timestamp
    created_at DateTime DEFAULT now(),
    updated_at DateTime DEFAULT now(),
    
    -- Indices
    INDEX idx_seller_date (seller_id, metric_date) TYPE minmax GRANULARITY 8192,
    INDEX idx_tenant_date (tenant_id, metric_date) TYPE minmax GRANULARITY 8192,
    
    -- Seller daily metrics for seller analytics dashboard
) ENGINE = ReplacingMergeTree(created_at)
PARTITION BY toYYYYMM(metric_date)
ORDER BY (tenant_id, seller_id, metric_date)
TTL metric_date + INTERVAL 730 DAY;

-- ============================================================================
-- CLV PREDICTIONS TABLE (ML Model Output)
-- ============================================================================

DROP TABLE IF EXISTS ch_clv_predictions;

CREATE TABLE ch_clv_predictions (
    -- Dimensions
    tenant_id UInt32,
    user_id UInt32,
    prediction_date Date,
    
    -- CLV Predictions (12-month, 24-month, lifetime)
    clv_12m Float64,
    clv_24m Float64,
    clv_lifetime Float64,
    
    -- Segments
    clv_segment Enum8('low' = 1, 'medium' = 2, 'high' = 3, 'vip' = 4),
    rfm_segment String,
    churn_probability Float32,
    
    -- RFM Scores
    recency_score UInt8,
    frequency_score UInt8,
    monetary_score UInt8,
    rfm_score UInt16,
    
    -- Feature Values (for explainability)
    total_orders UInt32,
    total_spent Float64,
    avg_order_value Float64,
    days_since_last_order UInt16,
    days_since_first_order UInt16,
    
    -- Model Info
    model_version String,
    model_confidence Float32,
    
    -- Timestamps
    created_at DateTime DEFAULT now(),
    updated_at DateTime DEFAULT now(),
    
    -- Indices
    INDEX idx_user_date (user_id, prediction_date) TYPE minmax GRANULARITY 8192,
    INDEX idx_tenant_date (tenant_id, prediction_date) TYPE minmax GRANULARITY 8192,
    INDEX idx_clv_segment (clv_segment) TYPE set(4) GRANULARITY 8192,
    INDEX idx_churn (churn_probability) TYPE minmax GRANULARITY 8192,
    
    -- CLV predictions from ML model (XGBoost/LightGBM)
) ENGINE = ReplacingMergeTree(updated_at)
PARTITION BY toYYYYMM(prediction_date)
ORDER BY (tenant_id, user_id, prediction_date)
TTL prediction_date + INTERVAL 365 DAY;

-- ============================================================================
-- A/B TEST ASSIGNMENTS TABLE
-- ============================================================================

DROP TABLE IF EXISTS ch_abtest_assignments;

CREATE TABLE ch_abtest_assignments (
    -- Identifiers
    tenant_id UInt32,
    test_id String,
    test_name String,
    user_id UInt32 DEFAULT 0,
    session_id String DEFAULT '',
    
    -- Assignment
    variant_id String,
    variant_name String,
    is_control UInt8,
    
    -- Context
    vertical String DEFAULT '',
    assignment_context String,  -- JSON: device, location, etc.
    
    -- Timestamps
    assigned_at DateTime,
    assignment_date Date MATERIALIZED toDate(assigned_at),
    
    -- Status
    is_exposed UInt8 DEFAULT 0,
    exposed_at Nullable(DateTime),
    
    -- Indices
    INDEX idx_test_user (test_id, user_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_test_date (test_id, assignment_date) TYPE minmax GRANULARITY 8192,
    INDEX idx_variant (variant_id) TYPE bloom_filter GRANULARITY 8192,
    
    -- A/B test user assignments
) ENGINE = ReplacingMergeTree(assigned_at)
PARTITION BY toYYYYMM(assignment_date)
ORDER BY (tenant_id, test_id, user_id, assigned_at)
TTL assigned_at + INTERVAL 180 DAY
SETTINGS allow_nullable_key = 1;

-- ============================================================================
-- A/B TEST RESULTS TABLE
-- ============================================================================

DROP TABLE IF EXISTS ch_abtest_results;

CREATE TABLE ch_abtest_results (
    -- Identifiers
    tenant_id UInt32,
    test_id String,
    test_name String,
    variant_id String,
    variant_name String,
    metric_date Date,
    
    -- Sample Size
    sample_size UInt64,
    users_exposed UInt64,
    users_converted UInt64,
    
    -- Metrics
    conversion_rate Float64,
    revenue_per_user Float64,
    avg_order_value Float64,
    
    -- Statistical Tests
    statistical_significance UInt8,
    p_value Float64,
    confidence_interval_lower Float64,
    confidence_interval_upper Float64,
    uplift_vs_control Float64,
    
    -- CUPED Adjusted (if applicable)
    cuped_conversion_rate Nullable(Float64),
    cuped_uplift Nullable(Float64),
    
    -- Bayesian (if applicable)
    bayesian_probability_to_win Nullable(Float64),
    expected_loss Nullable(Float64),
    
    -- Timestamp
    created_at DateTime DEFAULT now(),
    
    -- Indices
    INDEX idx_test_variant_date (test_id, variant_id, metric_date) TYPE minmax GRANULARITY 8192,
    INDEX idx_test_date (test_id, metric_date) TYPE minmax GRANULARITY 8192,
    
    -- A/B test aggregated results with statistical analysis
) ENGINE = ReplacingMergeTree(created_at)
PARTITION BY toYYYYMM(metric_date)
ORDER BY (tenant_id, test_id, variant_id, metric_date)
TTL metric_date + INTERVAL 365 DAY;

-- ============================================================================
-- BUYER SELLER FEATURES TABLE (Feature Store for Matching/Recommendations)
-- ============================================================================

DROP TABLE IF EXISTS ch_buyer_seller_features;

CREATE TABLE ch_buyer_seller_features (
    -- Dimensions
    tenant_id UInt32,
    buyer_id UInt32,
    seller_id UInt32,
    feature_date Date,
    
    -- Buyer Features
    buyer_clv_segment String,
    buyer_clv_score Float32,
    buyer_rfm_segment String,
    buyer_total_orders UInt32,
    buyer_total_spent Float64,
    buyer_avg_order_value Float64,
    buyer_days_since_last_order UInt16,
    buyer_preferred_categories Array(String),
    
    -- Seller Features
    seller_rating_avg Float32,
    seller_review_count UInt32,
    seller_products_active UInt16,
    seller_fulfillment_rate Float32,
    seller_cancellation_rate Float32,
    seller_vertical String,
    seller_price_range_low Float64,
    seller_price_range_high Float64,
    
    -- Interaction Features
    buyer_seller_order_count UInt32,
    buyer_seller_total_spent Float64,
    buyer_seller_last_order_date Nullable(Date),
    buyer_seller_avg_rating Nullable(Float32),
    
    -- Similarity Scores (for recommendations)
    category_similarity_score Float32,
    price_preference_score Float32,
    overall_affinity_score Float32,
    
    -- Timestamp
    created_at DateTime DEFAULT now(),
    
    -- Indices
    INDEX idx_buyer_seller (buyer_id, seller_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_buyer_date (buyer_id, feature_date) TYPE minmax GRANULARITY 8192,
    INDEX idx_seller_date (seller_id, feature_date) TYPE minmax GRANULARITY 8192,
    
    -- Buyer-Seller feature pairs for matching and recommendations
) ENGINE = ReplacingMergeTree(created_at)
PARTITION BY toYYYYMM(feature_date)
ORDER BY (tenant_id, buyer_id, seller_id, feature_date)
TTL feature_date + INTERVAL 90 DAY;

-- ============================================================================
-- MATERIALIALIZED VIEWS (Real-time Aggregations)
-- ============================================================================

-- Hourly events count by type
DROP TABLE IF EXISTS ch_events_hourly;

CREATE TABLE ch_events_hourly (
    tenant_id UInt32,
    event_type String,
    event_category String,
    hour DateTime,
    
    event_count UInt64,
    unique_users UInt64,
    unique_sessions UInt64,
    monetary_value_sum Float64,
    
    -- Hourly event aggregation
) ENGINE = SummingMergeTree()
ORDER BY (tenant_id, hour, event_type)
PARTITION BY toYYYYMM(hour);

CREATE MATERIALIZED VIEW ch_events_hourly_mv TO ch_events_hourly AS
SELECT
    tenant_id,
    event_type,
    event_category,
    event_hour AS hour,
    COUNT(*) AS event_count,
    uniq(user_id) AS unique_users,
    uniq(session_id) AS unique_sessions,
    sum(monetary_value) AS monetary_value_sum
FROM ch_raw_events
GROUP BY tenant_id, event_type, event_category, hour;

-- Daily metrics MV (from raw events)
DROP VIEW IF EXISTS ch_daily_metrics_mv;

CREATE MATERIALIZED VIEW ch_daily_metrics_mv TO ch_daily_metrics AS
SELECT
    tenant_id,
    event_date AS metric_date,
    assumeNotNull(any(vertical)) AS vertical,
    assumeNotNull(any(seller_id)) AS seller_id,
    assumeNotNull(any(product_id)) AS product_id,
    
    -- Orders
    countIf(event_type = 'order.placed') AS orders_total,
    countIf(event_type = 'order.delivered') AS orders_completed,
    countIf(event_type = 'order.cancelled') AS orders_cancelled,
    coalesce(sumIf(monetary_value, event_type = 'order.placed'), 0) AS gmv_total,
    coalesce(sumIf(monetary_value, event_type = 'order.delivered'), 0) AS gmv_completed,
    0 AS aov_avg,
    
    -- Users
    countIf(event_type = 'user.registered') AS users_new,
    uniq(user_id) AS users_active,
    0 AS users_returning,
    uniq(session_id) AS sessions_total,
    
    -- Products
    countIf(event_type = 'product.viewed') AS product_views,
    countIf(event_type = 'product.added_to_cart') AS product_adds_to_cart,
    countIf(event_type = 'product.purchased') AS product_purchases,
    
    -- Conversion
    0 AS conversion_rate_funnel,
    0 AS conversion_rate_cart_to_order,
    
    -- Revenue
    coalesce(sumIf(monetary_value, event_type = 'order.paid'), 0) AS revenue_total,
    coalesce(sumIf(monetary_value, event_type = 'order.refunded'), 0) AS revenue_refunded,
    
    now() AS created_at
FROM ch_raw_events
GROUP BY tenant_id, event_date;

-- ============================================================================
-- SYSTEM SETTINGS
-- ============================================================================

-- Optimize for high-throughput ingestion
-- SET max_insert_threads = 8;
-- SET max_insert_block_size = 1048576;
-- SET background_pool_size = 32;

-- Compression for storage efficiency
-- SET compression_codec = 'ZSTD';
-- SET compression_level = 3;

-- Query cache for dashboard queries
-- SET query_cache_max_size_in_bytes = 1073741824;  -- 1GB
-- SET query_cache_ttl = 300;  -- 5 minutes

-- ============================================================================
-- HEALTH CHECK
-- ============================================================================

SELECT 'ClickHouse Big Data Schema Installation Complete' AS status,
       now() AS created_at,
       version() AS clickhouse_version,
       'Tables: raw_events, daily_metrics, seller_metrics, clv_predictions, abtest_*' AS components;
