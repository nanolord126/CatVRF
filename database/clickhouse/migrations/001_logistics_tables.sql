-- ClickHouse Logistics AI Tables Migration
-- Created: 18.04.2026
-- Phase 1: Raw Events Layer

-- Drop existing tables (for fresh setup)
DROP TABLE IF EXISTS logistics_orders_raw ON CLUSTER default;
DROP TABLE IF EXISTS logistics_courier_locations_raw ON CLUSTER default;
DROP TABLE IF EXISTS logistics_pvz_load_raw ON CLUSTER default;
DROP TABLE IF EXISTS logistics_traffic_raw ON CLUSTER default;

-- ──────────────────────────────────────────────────────────────────
-- Raw Events Layer
-- ──────────────────────────────────────────────────────────────────

CREATE TABLE logistics_orders_raw (
    -- Identifiers
    order_id UUID,
    tenant_id UInt32,
    vertical String,
    
    -- Order details
    user_id UInt32,
    merchant_id UInt32,
    order_type Enum8('courier' = 1, 'pvz' = 2, 'taxi' = 3),
    
    -- Location
    pickup_lat Float64,
    pickup_lon Float64,
    delivery_lat Float64,
    delivery_lon Float64,
    pvz_id Nullable(UInt32),
    
    -- Package
    weight_kg Float32,
    volume_cm3 Float32,
    items_count UInt16,
    
    -- Timing
    created_at DateTime,
    accepted_at Nullable(DateTime),
    picked_up_at Nullable(DateTime),
    delivered_at Nullable(DateTime),
    cancelled_at Nullable(DateTime),
    
    -- Outcomes
    status String,
    cancellation_reason Nullable(String),
    
    -- Context
    surge_multiplier Float32,
    base_price_kopek UInt32,
    final_price_kopek UInt32,
    payment_method String,
    
    -- Metadata
    correlation_id String,
    metadata String,
    
    -- Indices
    INDEX idx_tenant_created (tenant_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_status (status) TYPE set(100) GRANULARITY 8192,
    INDEX idx_pickup_geo (pickup_lat, pickup_lon) TYPE minmax GRANULARITY 8192,
    
    COMMENT 'Raw order events for logistics ML'
) ENGINE = MergeTree()
ORDER BY (tenant_id, created_at, order_id)
PARTITION BY toYYYYMMDD(created_at)
TTL created_at + INTERVAL 730 DAY;

CREATE TABLE logistics_courier_locations_raw (
    -- Identifiers
    courier_id UInt32,
    tenant_id UInt32,
    location_id UUID,
    
    -- Location
    latitude Float64,
    longitude Float64,
    geo_hash String,
    accuracy_meters Nullable(Float32),
    
    -- Courier state
    status Enum8('idle' = 1, 'busy' = 2, 'offline' = 3),
    current_order_id Nullable(UUID),
    battery_level Nullable(UInt8),
    is_taxi UInt8,
    
    -- Timing
    recorded_at DateTime,
    created_at DateTime,
    
    -- Context
    speed_kmh Nullable(Float32),
    heading_degrees Nullable(Float32),
    
    -- Indices
    INDEX idx_courier_time (courier_id, recorded_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_geo_hash (geo_hash) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_status (status) TYPE set(10) GRANULARITY 8192,
    
    COMMENT 'Real-time courier GPS positions (every 10-30s)'
) ENGINE = MergeTree()
ORDER BY (courier_id, recorded_at)
PARTITION BY toYYYYMMDD(recorded_at)
TTL recorded_at + INTERVAL 90 DAY;

CREATE TABLE logistics_pvz_load_raw (
    pvz_id UInt32,
    tenant_id UInt32,
    
    -- Load metrics
    active_orders UInt16,
    available_slots UInt16,
    total_capacity UInt16,
    
    -- Location
    latitude Float64,
    longitude Float64,
    geo_hash String,
    
    -- Timing
    recorded_at DateTime,
    
    -- Context
    zone_id Nullable(UInt32),
    surge_multiplier Float32,
    
    COMMENT 'PVZ load metrics for demand forecasting'
) ENGINE = MergeTree()
ORDER BY (pvz_id, recorded_at)
PARTITION BY toYYYYMMDD(recorded_at)
TTL recorded_at + INTERVAL 180 DAY;

CREATE TABLE logistics_traffic_raw (
    -- Location
    geo_hash String,
    latitude Float64,
    longitude Float64,
    
    -- Traffic data (from external API: Yandex Traffic, Google Maps)
    traffic_level Enum8('low' = 1, 'moderate' = 2, 'high' = 3, 'severe' = 4),
    speed_kmh Nullable(Float32),
    delay_minutes Nullable(Float32),
    
    -- Weather context
    temperature_c Nullable(Float32),
    precipitation_mm Nullable(Float32),
    weather_condition Nullable(String),
    
    -- Timing
    recorded_at DateTime,
    
    -- Source
    data_source String,
    
    COMMENT 'Traffic and weather data from external APIs'
) ENGINE = MergeTree()
ORDER BY (geo_hash, recorded_at)
PARTITION BY toYYYYMMDD(recorded_at)
TTL recorded_at + INTERVAL 30 DAY;

-- ──────────────────────────────────────────────────────────────────
-- Feature Store Layer
-- ──────────────────────────────────────────────────────────────────

DROP TABLE IF EXISTS feature_store_courier_hourly ON CLUSTER default;
DROP TABLE IF EXISTS feature_store_pvz_hourly ON CLUSTER default;
DROP TABLE IF EXISTS feature_store_route_hourly ON CLUSTER default;
DROP TABLE IF EXISTS feature_store_demand_forecasting ON CLUSTER default;

CREATE TABLE feature_store_courier_hourly (
    courier_id UInt32,
    tenant_id UInt32,
    hour DateTime,
    
    -- Historical features (last 1h, 4h, 24h)
    orders_completed_1h UInt16,
    orders_completed_4h UInt16,
    orders_completed_24h UInt16,
    
    avg_delivery_time_1h Float32,
    avg_delivery_time_4h Float32,
    avg_delivery_time_24h Float32,
    
    total_distance_1h_km Float32,
    total_distance_4h_km Float32,
    total_distance_24h_km Float32,
    
    acceptance_rate_1h Float32,
    acceptance_rate_4h Float32,
    acceptance_rate_24h Float32,
    
    -- Current state
    current_status String,
    current_load UInt8,
    battery_level Nullable(UInt8),
    
    -- Location features
    last_geo_hash String,
    hours_since_last_order Nullable(Float32),
    
    COMMENT 'Courier features for assignment model'
) ENGINE = MergeTree()
ORDER BY (courier_id, hour)
PARTITION BY toYYYYMM(hour);

CREATE TABLE feature_store_pvz_hourly (
    pvz_id UInt32,
    tenant_id UInt32,
    hour DateTime,
    
    -- Load features
    avg_load_1h Float32,
    avg_load_4h Float32,
    avg_load_24h Float32,
    
    max_load_24h UInt16,
    min_available_slots_24h UInt16,
    
    -- Demand features
    orders_received_1h UInt16,
    orders_received_4h UInt16,
    orders_received_24h UInt16,
    
    -- Time features
    hour_of_day UInt8,
    day_of_week UInt8,
    is_weekend UInt8,
    is_holiday UInt8,
    
    -- Location
    geo_hash String,
    
    COMMENT 'PVZ features for assignment model'
) ENGINE = MergeTree()
ORDER BY (pvz_id, hour)
PARTITION BY toYYYYMM(hour);

CREATE TABLE feature_store_route_hourly (
    route_id UUID,
    tenant_id UInt32,
    hour DateTime,
    
    -- Route features
    distance_km Float32,
    estimated_duration_min UInt16,
    actual_duration_min Nullable(UInt16),
    
    -- ETA prediction features
    pickup_geo_hash String,
    delivery_geo_hash String,
    
    -- Time features
    hour_of_day UInt8,
    day_of_week UInt8,
    is_weekend UInt8,
    
    -- Traffic features
    traffic_level_at_pickup Nullable(UInt8),
    traffic_level_at_delivery Nullable(UInt8),
    avg_speed_kmh Nullable(Float32),
    
    -- Weather features
    temperature_c Nullable(Float32),
    precipitation_mm Nullable(Float32),
    
    -- Outcome
    delay_minutes Nullable(Float32),
    is_delayed UInt8,
    
    COMMENT 'Route features for ETA prediction model'
) ENGINE = MergeTree()
ORDER BY (route_id, hour)
PARTITION BY toYYYYMM(hour);

CREATE TABLE feature_store_demand_forecasting (
    geo_hash String,
    tenant_id UInt32,
    hour DateTime,
    
    -- Demand metrics
    orders_count UInt16,
    unique_users UInt16,
    
    -- Historical lag features
    orders_1h_ago UInt16,
    orders_2h_ago UInt16,
    orders_4h_ago UInt16,
    orders_24h_ago UInt16,
    orders_7d_ago UInt16,
    
    -- Rolling averages
    avg_orders_4h Float32,
    avg_orders_24h Float32,
    avg_orders_7d Float32,
    
    -- Time features
    hour_of_day UInt8,
    day_of_week UInt8,
    day_of_month UInt8,
    month UInt8,
    is_weekend UInt8,
    is_holiday UInt8,
    
    -- Context
    weather_condition Nullable(String),
    temperature_c Nullable(Float32),
    
    COMMENT 'Demand forecasting features (2-6h ahead)'
) ENGINE = MergeTree()
ORDER BY (geo_hash, hour)
PARTITION BY toYYYYMM(hour);

-- ──────────────────────────────────────────────────────────────────
-- Predictions Layer (ML Outputs)
-- ──────────────────────────────────────────────────────────────────

DROP TABLE IF EXISTS ml_predictions_courier_assignment ON CLUSTER default;
DROP TABLE IF EXISTS ml_predictions_eta ON CLUSTER default;
DROP TABLE IF EXISTS ml_predictions_pvz_scoring ON CLUSTER default;

CREATE TABLE ml_predictions_courier_assignment (
    prediction_id UUID,
    tenant_id UInt32,
    order_id UUID,
    
    -- Input features (snapshot)
    courier_candidates_ids Array(UInt32),
    pickup_geo_hash String,
    delivery_geo_hash String,
    order_weight_kg Float32,
    
    -- Prediction
    selected_courier_id UInt32,
    model_version String,
    prediction_confidence Float32,
    
    -- Alternative scores
    all_courier_scores Array(Float32),
    
    -- Context
    predicted_at DateTime,
    actual_courier_id Nullable(UInt32),
    actual_outcome Nullable(String),
    actual_acceptance_time_seconds Nullable(UInt32),
    
    -- Performance
    eta_prediction_error_minutes Nullable(Float32),
    
    COMMENT 'Courier assignment predictions + actual outcomes'
) ENGINE = MergeTree()
ORDER BY (prediction_id, predicted_at)
PARTITION BY toYYYYMMDD(predicted_at)
TTL predicted_at + INTERVAL 365 DAY;

CREATE TABLE ml_predictions_eta (
    prediction_id UUID,
    tenant_id UInt32,
    route_id UUID,
    order_id UUID,
    
    -- Input features
    pickup_lat Float64,
    pickup_lon Float64,
    delivery_lat Float64,
    delivery_lon Float64,
    distance_km Float32,
    transport_type String,
    
    -- Prediction
    predicted_eta_minutes UInt16,
    model_version String,
    prediction_confidence Float32,
    
    -- Context
    predicted_at DateTime,
    
    -- Actual outcome
    actual_eta_minutes Nullable(UInt16),
    error_minutes Nullable(Float32),
    is_delayed Nullable(UInt8),
    
    COMMENT 'ETA predictions + actual outcomes'
) ENGINE = MergeTree()
ORDER BY (prediction_id, predicted_at)
PARTITION BY toYYYYMMDD(predicted_at)
TTL predicted_at + INTERVAL 365 DAY;

CREATE TABLE ml_predictions_pvz_scoring (
    prediction_id UUID,
    tenant_id UInt32,
    order_id UUID,
    
    -- Input features
    user_geo_hash String,
    available_pvz_ids Array(UInt32),
    order_type String,
    
    -- Prediction
    recommended_pvz_id UInt32,
    model_version String,
    prediction_confidence Float32,
    
    -- All scores
    all_pvz_scores Array(Float32),
    
    -- Context
    predicted_at DateTime,
    actual_pvz_id Nullable(UInt32),
    user_accepted UInt8,
    
    COMMENT 'PVZ scoring predictions + user choices'
) ENGINE = MergeTree()
ORDER BY (prediction_id, predicted_at)
PARTITION BY toYYYYMMDD(predicted_at)
TTL predicted_at + INTERVAL 365 DAY;

-- Health Check
SELECT 'ClickHouse Logistics Tables Migration Complete' AS status,
       now() AS created_at,
       version() AS clickhouse_version;
