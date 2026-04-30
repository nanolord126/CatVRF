-- ClickHouse Feature Store for Agentic Logistics
-- Phase 4: Offline + Online Feature Store
-- Created: April 18, 2026

-- ============================================================================
-- RAW EVENT TABLES (Logistics-specific)
-- ============================================================================

-- Shipment events with real-time tracking
DROP TABLE IF EXISTS ch_logistics_shipments ON CLUSTER default;

CREATE TABLE ch_logistics_shipments (
    -- Identifiers
    id UUID,
    tenant_id UInt32,
    vertical String,
    shipment_id String,
    order_id String,
    
    -- Location data
    pickup_lat Float64,
    pickup_lon Float64,
    delivery_lat Float64,
    delivery_lon Float64,
    current_lat Nullable(Float64),
    current_lon Nullable(Float64),
    
    -- Courier/Taxi data
    courier_id Nullable(String),
    taxi_id Nullable(String),
    driver_acceptance_rate Nullable(Float32),
    
    -- Timing data
    created_at DateTime,
    pickup_scheduled_at Nullable(DateTime),
    pickup_actual_at Nullable(DateTime),
    delivery_scheduled_at Nullable(DateTime),
    delivery_actual_at Nullable(DateTime),
    
    -- Predictions vs Reality
    eta_predicted_minutes Nullable(Float32),
    eta_actual_minutes Nullable(Float32),
    eta_error_minutes Nullable(Float32),
    
    -- Context features
    weather_condition String,
    temperature_c Nullable(Float32),
    traffic_level Enum8('low' = 1, 'medium' = 2, 'high' = 3, 'severe' = 4),
    hour_of_day UInt8,
    day_of_week UInt8,
    is_weekend UInt8,
    is_holiday UInt8,
    
    -- Cancellation reasons
    status String,
    cancellation_reason Nullable(String),
    cancellation_category Nullable(String),  -- 'driver', 'customer', 'system', 'traffic'
    
    -- PVZ data (if applicable)
    pvz_id Nullable(String),
    pvz_name Nullable(String),
    pvz_load_at_booking Nullable(UInt16),
    
    -- Distance/Time metrics
    distance_km Nullable(Float32),
    actual_distance_km Nullable(Float32),
    
    -- Indices
    INDEX idx_tenant_created (tenant_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_courier_id (courier_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_status (status) TYPE set(100) GRANULARITY 8192,
    INDEX idx_pvz_id (pvz_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_hour_day (hour_of_day, day_of_week) TYPE minmax GRANULARITY 8192,
    
    COMMENT 'Raw shipment events with tracking and prediction data'
) ENGINE = MergeTree()
ORDER BY (tenant_id, created_at, shipment_id)
PARTITION BY toYYYYMMDD(created_at)
TTL created_at + INTERVAL 730 DAY;

-- Courier/Taxi position updates (every 15 seconds)
DROP TABLE IF EXISTS ch_logistics_positions ON CLUSTER default;

CREATE TABLE ch_logistics_positions (
    -- Identifiers
    id UUID,
    tenant_id UInt32,
    vertical String,
    
    -- Courier/Taxi data
    courier_id String,
    vehicle_type Enum8('courier_bike' = 1, 'courier_car' = 2, 'taxi' = 3, 'truck' = 4),
    is_available UInt8,
    
    -- Position data
    latitude Float64,
    longitude Float64,
    velocity_kmh Nullable(Float32),
    heading_deg Nullable(Float32),
    
    -- Cluster/Zone data
    geo_hash String,
    zone_id Nullable(String),
    
    -- Status
    current_status Enum8(
        'idle' = 1,
        'moving_to_pickup' = 2,
        'at_pickup' = 3,
        'moving_to_delivery' = 4,
        'at_delivery' = 5,
        'returning' = 6
    ),
    current_shipment_id Nullable(String),
    
    -- Timestamp
    created_at DateTime,
    
    -- Indices
    INDEX idx_tenant_created (tenant_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_courier_id (courier_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_geo_hash (geo_hash) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_zone_id (zone_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_is_available (is_available) TYPE set(2) GRANULARITY 8192,
    
    COMMENT 'Courier/taxi position updates every 15 seconds'
) ENGINE = MergeTree()
ORDER BY (tenant_id, courier_id, created_at)
PARTITION BY toYYYYMMDD(created_at)
TTL created_at + INTERVAL 30 DAY;

-- PVZ (Pickup Point) load and metrics
DROP TABLE IF EXISTS ch_logistics_pvz ON CLUSTER default;

CREATE TABLE ch_logistics_pvz (
    -- Identifiers
    id UUID,
    tenant_id UInt32,
    vertical String,
    
    -- PVZ data
    pvz_id String,
    pvz_name String,
    pvz_lat Float64,
    pvz_lon Float64,
    geo_hash String,
    zone_id String,
    
    -- Load metrics
    total_lockers UInt16,
    occupied_lockers UInt16,
    available_lockers UInt16,
    load_ratio Float32,
    
    -- Time windows
    hour DateTime,
    hour_of_day UInt8,
    day_of_week UInt8,
    
    -- User preferences
    unique_users UInt32,
    repeat_users UInt32,
    preference_score Float32,  -- How often users choose this PVZ
    
    -- Performance metrics
    avg_pickup_time_minutes Nullable(Float32),
    avg_wait_time_minutes Nullable(Float32),
    
    -- Timestamp
    created_at DateTime,
    
    -- Indices
    INDEX idx_tenant_created (tenant_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_pvz_id (pvz_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_zone_id (zone_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_hour (hour) TYPE minmax GRANULARITY 8192,
    
    COMMENT 'PVZ load metrics and user preferences'
) ENGINE = MergeTree()
ORDER BY (tenant_id, pvz_id, hour)
PARTITION BY toYYYYMM(hour)
TTL created_at + INTERVAL 365 DAY;

-- ============================================================================
-- MATERIALIALIZED VIEWS (Precomputed Features)
-- ============================================================================

-- PVZ load ratio by hour (temporal feature)
DROP TABLE IF EXISTS ch_feature_pvz_load_ratio ON CLUSTER default;

CREATE TABLE ch_feature_pvz_load_ratio (
    tenant_id UInt32,
    vertical String,
    pvz_id String,
    zone_id String,
    hour DateTime,
    hour_of_day UInt8,
    day_of_week UInt8,
    
    load_ratio_avg Float32,
    load_ratio_max Float32,
    load_ratio_min Float32,
    load_ratio_p50 Float32,
    load_ratio_p95 Float32,
    
    unique_users UInt32,
    preference_score_avg Float32,
    
    COMMENT 'PVZ load ratio features by hour (for temporal prediction)'
) ENGINE = AggregatingMergeTree()
ORDER BY (tenant_id, pvz_id, hour)
PARTITION BY toYYYYMM(hour);

CREATE MATERIALIZED VIEW ch_feature_pvz_load_ratio_mv TO ch_feature_pvz_load_ratio AS
SELECT
    tenant_id,
    vertical,
    pvz_id,
    zone_id,
    hour,
    hour_of_day,
    day_of_week,
    
    avgState(load_ratio) AS load_ratio_avg,
    maxState(load_ratio) AS load_ratio_max,
    minState(load_ratio) AS load_ratio_min,
    quantileState(0.5)(load_ratio) AS load_ratio_p50,
    quantileState(0.95)(load_ratio) AS load_ratio_p95,
    
    sumState(unique_users) AS unique_users,
    avgState(preference_score) AS preference_score_avg
FROM ch_logistics_pvz
GROUP BY tenant_id, vertical, pvz_id, zone_id, hour, hour_of_day, day_of_week;

-- Courier speed by zone and hour
DROP TABLE IF EXISTS ch_feature_courier_speed ON CLUSTER default;

CREATE TABLE ch_feature_courier_speed (
    tenant_id UInt32,
    vertical String,
    zone_id String,
    vehicle_type String,
    hour DateTime,
    hour_of_day UInt8,
    day_of_week UInt8,
    
    avg_speed_kmh Float32,
    p50_speed_kmh Float32,
    p95_speed_kmh Float32,
    count_positions UInt64,
    
    traffic_level_avg UInt8,
    
    COMMENT 'Courier speed features by zone and hour (for ETA prediction)'
) ENGINE = AggregatingMergeTree()
ORDER BY (tenant_id, zone_id, vehicle_type, hour)
PARTITION BY toYYYYMM(hour);

CREATE MATERIALIZED VIEW ch_feature_courier_speed_mv TO ch_feature_courier_speed AS
SELECT
    tenant_id,
    vertical,
    zone_id,
    toString(vehicle_type) AS vehicle_type,
    toStartOfHour(created_at) AS hour,
    toHour(created_at) AS hour_of_day,
    toDayOfWeek(created_at) AS day_of_week,
    
    avgState(velocity_kmh) AS avg_speed_kmh,
    quantileState(0.5)(velocity_kmh) AS p50_speed_kmh,
    quantileState(0.95)(velocity_kmh) AS p95_speed_kmh,
    countState(*) AS count_positions,
    
    avgState(toUInt8(
        CASE 
            WHEN velocity_kmh < 15 THEN 4  -- severe
            WHEN velocity_kmh < 25 THEN 3  -- high
            WHEN velocity_kmh < 35 THEN 2  -- medium
            ELSE 1  -- low
        END
    )) AS traffic_level_avg
FROM ch_logistics_positions
WHERE velocity_kmh IS NOT NULL
GROUP BY tenant_id, vertical, zone_id, vehicle_type, hour, hour_of_day, day_of_week;

-- Demand forecast by zone (2-hour ahead)
DROP TABLE IF EXISTS ch_feature_demand_forecast ON CLUSTER default;

CREATE TABLE ch_feature_demand_forecast (
    tenant_id UInt32,
    vertical String,
    zone_id String,
    forecast_hour DateTime,
    hour_of_day UInt8,
    day_of_week UInt8,
    
    order_count UInt32,
    order_count_ema Float32,  -- Exponential moving average
    
    courier_count_available UInt32,
    supply_demand_ratio Float32,
    
    avg_eta_predicted Float32,
    avg_eta_error Float32,
    
    cancellation_rate Float32,
    driver_acceptance_rate_avg Float32,
    
    COMMENT 'Demand forecasting features by zone (2-hour ahead prediction)'
) ENGINE = MergeTree()
ORDER BY (tenant_id, zone_id, forecast_hour)
PARTITION BY toYYYYMM(forecast_hour)
TTL forecast_hour + INTERVAL 90 DAY;

CREATE MATERIALIZED VIEW ch_feature_demand_forecast_mv TO ch_feature_demand_forecast AS
SELECT
    tenant_id,
    vertical,
    geo_hash AS zone_id,
    toStartOfHour(created_at + INTERVAL 2 HOUR) AS forecast_hour,
    toHour(created_at + INTERVAL 2 HOUR) AS hour_of_day,
    toDayOfWeek(created_at + INTERVAL 2 HOUR) AS day_of_week,
    
    count(*) AS order_count,
    -- EMA would be calculated via separate query/job
    
    0 AS courier_count_available,  -- Would join with positions table
    0.0 AS supply_demand_ratio,
    
    avg(eta_predicted_minutes) AS avg_eta_predicted,
    avg(abs(eta_error_minutes)) AS avg_eta_error,
    
    countIf(status = 'cancelled') / count(*) AS cancellation_rate,
    avg(driver_acceptance_rate) AS driver_acceptance_rate_avg
FROM ch_logistics_shipments
WHERE created_at > now() - INTERVAL 30 DAY
GROUP BY tenant_id, vertical, geo_hash, forecast_hour, hour_of_day, day_of_week;

-- ETA prediction features (point-in-time correct)
DROP TABLE IF EXISTS ch_feature_eta_training ON CLUSTER default;

CREATE TABLE ch_feature_eta_training (
    -- Identifiers
    tenant_id UInt32,
    vertical String,
    shipment_id String,
    event_time DateTime,
    
    -- Features at prediction time (point-in-time correct)
    pickup_lat Float64,
    pickup_lon Float64,
    delivery_lat Float64,
    delivery_lon Float64,
    distance_km Float32,
    
    hour_of_day UInt8,
    day_of_week UInt8,
    is_weekend UInt8,
    is_holiday UInt8,
    
    weather_condition String,
    temperature_c Float32,
    traffic_level UInt8,
    
    zone_pickup String,
    zone_delivery String,
    
    courier_vehicle_type String,
    courier_acceptance_rate Float32,
    
    -- Historical features (from past 24h)
    avg_speed_pickup_zone_last_24h Float32,
    avg_eta_error_pickup_zone_last_24h Float32,
    demand_pickup_zone_last_24h UInt32,
    
    -- Target
    eta_actual_minutes Float32,
    
    COMMENT 'Point-in-time correct features for ETA model training'
) ENGINE = MergeTree()
ORDER BY (tenant_id, event_time, shipment_id)
PARTITION BY toYYYYMM(event_time)
TTL event_time + INTERVAL 365 DAY;

-- ============================================================================
-- FEATURE STORE VIEWS (Online Serving)
-- ============================================================================

-- Online features for courier assignment (low latency)
DROP VIEW IF EXISTS ch_online_courier_assignment_features ON CLUSTER default;

CREATE VIEW ch_online_courier_assignment_features AS
SELECT
    s.tenant_id,
    s.vertical,
    s.shipment_id,
    s.pickup_lat,
    s.pickup_lon,
    s.delivery_lat,
    s.delivery_lon,
    s.distance_km,
    
    -- Current courier features
    p.courier_id,
    p.vehicle_type,
    p.is_available,
    p.latitude AS courier_lat,
    p.longitude AS courier_lon,
    p.velocity_kmh,
    p.current_status,
    
    -- Zone features
    p.geo_hash AS courier_zone,
    s.geo_hash AS pickup_zone,
    
    -- Speed features (last hour)
    cs.avg_speed_kmh AS zone_avg_speed,
    cs.p50_speed_kmh AS zone_median_speed,
    cs.traffic_level_avg,
    
    -- Demand features
    df.order_count AS zone_demand_last_2h,
    df.supply_demand_ratio,
    df.cancellation_rate,
    
    -- Distance from courier to pickup
    -- Would calculate via geodistance function
    
    s.created_at AS feature_timestamp
FROM ch_logistics_shipments s
LEFT JOIN ch_logistics_positions p 
    ON s.tenant_id = p.tenant_id 
    AND s.courier_id = p.courier_id
    AND p.created_at >= s.created_at - INTERVAL 5 MINUTE
LEFT JOIN ch_feature_courier_speed cs
    ON p.tenant_id = cs.tenant_id
    AND p.zone_id = cs.zone_id
    AND p.vehicle_type = cs.vehicle_type
    AND toStartOfHour(s.created_at) = cs.hour
LEFT JOIN ch_feature_demand_forecast df
    ON s.tenant_id = df.tenant_id
    AND s.geo_hash = df.zone_id
    AND toStartOfHour(s.created_at) = df.forecast_hour
WHERE s.status IN ('pending', 'assigned')
  AND p.created_at = (
      SELECT max(created_at) 
      FROM ch_logistics_positions 
      WHERE tenant_id = p.tenant_id 
        AND courier_id = p.courier_id
  );

-- Online features for PVZ scoring
DROP VIEW IF EXISTS ch_online_pvz_scoring_features ON CLUSTER default;

CREATE VIEW ch_online_pvz_scoring_features AS
SELECT
    pv.tenant_id,
    pv.vertical,
    pv.pvz_id,
    pv.pvz_name,
    pv.pvz_lat,
    pv.pvz_lon,
    pv.geo_hash,
    pv.zone_id,
    
    -- Current load
    pv.total_lockers,
    pv.occupied_lockers,
    pv.available_lockers,
    pv.load_ratio,
    
    -- Temporal features (next 3 hours forecast)
    plr.load_ratio_avg AS historical_load_avg,
    plr.load_ratio_p95 AS historical_load_p95,
    plr.preference_score_avg AS user_preference_score,
    
    -- Performance
    pv.avg_pickup_time_minutes,
    pv.avg_wait_time_minutes,
    
    -- Demand in zone
    df.order_count AS zone_demand,
    df.courier_count_available,
    
    pv.created_at AS feature_timestamp
FROM ch_logistics_pvz pv
LEFT JOIN ch_feature_pvz_load_ratio plr
    ON pv.tenant_id = plr.tenant_id
    AND pv.pvz_id = plr.pvz_id
    AND pv.hour = plr.hour
LEFT JOIN ch_feature_demand_forecast df
    ON pv.tenant_id = df.tenant_id
    AND pv.zone_id = df.zone_id
    AND toStartOfHour(pv.created_at + INTERVAL 3 HOUR) = df.forecast_hour
WHERE pv.created_at >= now() - INTERVAL 1 HOUR;

-- ============================================================================
-- PARQUET EXPORT CONFIGURATION
-- ============================================================================

-- Function to export training data to Parquet (called via scheduled job)
-- This would be executed via ClickHouse client or Python script
/*
Example export command:
clickhouse-client --query "
SELECT * FROM ch_feature_eta_training 
WHERE event_time >= now() - INTERVAL 7 DAY
FORMAT Parquet
" --output-file /data/training/eta_features_$(date +%Y%m%d).parquet
*/

-- ============================================================================
-- SYSTEM SETTINGS
-- ============================================================================

-- Optimize for feature store workloads
SET max_threads = 8;
SET max_memory_usage = 10000000000;  -- 10GB
SET allow_experimental_object_type = 1;

-- Compression for storage efficiency
SET compression_codec = 'ZSTD';
SET compression_level = 3;

-- Query cache for online features
SET query_cache_max_size_in_bytes = 536870912;  -- 512MB
SET query_cache_ttl = 300;  -- 5 minutes for online features

-- ============================================================================
-- HEALTH CHECK
-- ============================================================================

SELECT 'ClickHouse Feature Store Installation Complete' AS status,
       now() AS created_at,
       version() AS clickhouse_version,
       'Tables: shipments, positions, pvz, features' AS components;
