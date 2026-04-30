-- ============================================================================
-- CatVRF Recommendation Engine - Feature Store ClickHouse Tables
-- ============================================================================

-- ============================================================================
-- User Features Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS feature_store_user ON CLUSTER '{cluster}' (
    tenant_id UInt32,
    user_id UInt32,
    features String,  -- JSON: {"dense": [...], "sparse": {...}, "version": 1}
    version UInt8 DEFAULT 1,
    updated_at DateTime DEFAULT now(),
    created_at DateTime DEFAULT now()
)
ENGINE = ReplacingMergeTree(updated_at)
PARTITION BY toYYYYMM(updated_at)
ORDER BY (tenant_id, user_id, updated_at)
TTL updated_at + INTERVAL 30 DAY;

-- ============================================================================
-- Item Features Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS feature_store_item ON CLUSTER '{cluster}' (
    tenant_id UInt32,
    item_id UInt32,
    seller_id UInt32,
    vertical String,
    embedding String,  -- JSON array of floats
    features String,  -- JSON: {"dense": [...], "sparse": {...}, "version": 1}
    score Float32 DEFAULT 0.5,
    is_active UInt8 DEFAULT 1,
    version UInt8 DEFAULT 1,
    updated_at DateTime DEFAULT now(),
    created_at DateTime DEFAULT now()
)
ENGINE = ReplacingMergeTree(updated_at)
PARTITION BY (tenant_id, toYYYYMM(updated_at))
ORDER BY (tenant_id, item_id, updated_at)
TTL updated_at + INTERVAL 30 DAY;

-- ============================================================================
-- Seller Features Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS feature_store_seller ON CLUSTER '{cluster}' (
    tenant_id UInt32,
    seller_id UInt32,
    features String,  -- JSON: {"dense": [...], "sparse": {...}, "version": 1}
    version UInt8 DEFAULT 1,
    updated_at DateTime DEFAULT now(),
    created_at DateTime DEFAULT now()
)
ENGINE = ReplacingMergeTree(updated_at)
PARTITION BY toYYYYMM(updated_at)
ORDER BY (tenant_id, seller_id, updated_at)
TTL updated_at + INTERVAL 30 DAY;

-- ============================================================================
-- Recommendation Logs Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS recommendation_logs ON CLUSTER '{cluster}' (
    id UInt64,
    tenant_id UInt32,
    user_id UInt32,
    item_id UInt32,
    seller_id UInt32,
    vertical String,
    score Float32,
    confidence Float32,
    source String,
    scenario String,
    position UInt16,
    model_version String,
    correlation_id String,
    context String,
    impressed_at Nullable(DateTime),
    clicked_at Nullable(DateTime),
    converted_at Nullable(DateTime),
    created_at DateTime DEFAULT now()
)
ENGINE = MergeTree()
PARTITION BY (tenant_id, toYYYYMM(created_at))
ORDER BY (tenant_id, user_id, item_id, created_at)
TTL created_at + INTERVAL 90 DAY;

-- ============================================================================
-- Recommendation Impressions Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS recommendation_impressions ON CLUSTER '{cluster}' (
    id UInt64,
    tenant_id UInt32,
    user_id UInt32,
    item_id UInt32,
    position UInt16,
    scenario String,
    source String,
    correlation_id String,
    created_at DateTime DEFAULT now()
)
ENGINE = MergeTree()
PARTITION BY (tenant_id, toYYYYMM(created_at))
ORDER BY (tenant_id, user_id, item_id, created_at)
TTL created_at + INTERVAL 90 DAY;

-- ============================================================================
-- Recommendation Clicks Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS recommendation_clicks ON CLUSTER '{cluster}' (
    id UInt64,
    impression_id UInt64,
    tenant_id UInt32,
    user_id UInt32,
    item_id UInt32,
    scenario String,
    correlation_id String,
    created_at DateTime DEFAULT now()
)
ENGINE = MergeTree()
PARTITION BY (tenant_id, toYYYYMM(created_at))
ORDER BY (tenant_id, user_id, item_id, created_at)
TTL created_at + INTERVAL 90 DAY;

-- ============================================================================
-- Recommendation Conversions Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS recommendation_conversions ON CLUSTER '{cluster}' (
    id UInt64,
    click_id UInt64,
    tenant_id UInt32,
    user_id UInt32,
    item_id UInt32,
    item_a UInt32,  -- For frequently bought together analysis
    item_b UInt32,
    seller_a UInt32,
    seller_b UInt32,
    revenue Float64,
    scenario String,
    correlation_id String,
    created_at DateTime DEFAULT now()
)
ENGINE = MergeTree()
PARTITION BY (tenant_id, toYYYYMM(created_at))
ORDER BY (tenant_id, user_id, item_id, created_at)
TTL created_at + INTERVAL 180 DAY;

-- ============================================================================
-- Model Drift Reports Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS model_drift_reports ON CLUSTER '{cluster}' (
    id UInt64,
    model_type String,
    model_version String,
    psi_value Float32,
    accuracy_drop Float32,
    ndcg_drop Float32,
    status String,  -- healthy, warning, critical, unknown
    checked_at DateTime,
    created_at DateTime DEFAULT now()
)
ENGINE = MergeTree()
PARTITION BY (model_type, toYYYYMM(checked_at))
ORDER BY (model_type, model_version, checked_at)
TTL checked_at + INTERVAL 180 DAY;

-- ============================================================================
-- Offline Metrics Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS offline_metrics ON CLUSTER '{cluster}' (
    id UInt64,
    model_version String,
    scenario String,
    metric_name String,  -- ndcg, precision, recall, auc, etc.
    metric_value Float64,
    created_at DateTime DEFAULT now()
)
ENGINE = MergeTree()
PARTITION BY (model_version, toYYYYMM(created_at))
ORDER BY (model_version, scenario, metric_name, created_at)
TTL created_at + INTERVAL 365 DAY;

-- ============================================================================
-- Materialized View: Click-Through Rate by Scenario
-- ============================================================================
CREATE MATERIALIZED VIEW IF NOT EXISTS mv_recommendation_ctr ON CLUSTER '{cluster}'
ENGINE = SummingMergeTree()
PARTITION BY (tenant_id, toYYYYMM(created_at))
ORDER BY (tenant_id, scenario, toStartOfHour(created_at))
AS
SELECT
    tenant_id,
    scenario,
    toStartOfHour(created_at) as hour,
    count() as impressions,
    countIf(click_id > 0) as clicks
FROM recommendation_impressions
LEFT JOIN recommendation_clicks ON recommendation_impressions.id = recommendation_clicks.impression_id
GROUP BY tenant_id, scenario, hour;

-- ============================================================================
-- Materialized View: Conversion Rate by Scenario
-- ============================================================================
CREATE MATERIALIZED VIEW IF NOT EXISTS mv_recommendation_conversion ON CLUSTER '{cluster}'
ENGINE = SummingMergeTree()
PARTITION BY (tenant_id, toYYYYMM(created_at))
ORDER BY (tenant_id, scenario, toStartOfHour(created_at))
AS
SELECT
    tenant_id,
    scenario,
    toStartOfHour(created_at) as hour,
    count() as clicks,
    countIf(conversion_id > 0) as conversions,
    sum(revenue) as total_revenue
FROM recommendation_clicks
LEFT JOIN recommendation_conversions ON recommendation_clicks.id = recommendation_conversions.click_id
GROUP BY tenant_id, scenario, hour;

-- ============================================================================
-- Indexes for Performance (ClickHouse 22.3+)
-- ============================================================================

-- User features index
-- ALTER TABLE feature_store_user ADD INDEX idx_user_tenant (tenant_id) TYPE minmax GRANULARITY 1;

-- Item features index
-- ALTER TABLE feature_store_item ADD INDEX idx_item_vertical (vertical) TYPE set(100) GRANULARITY 1;

-- Recommendation logs index
-- ALTER TABLE recommendation_logs ADD INDEX idx_scenario (scenario) TYPE set(50) GRANULARITY 1;
