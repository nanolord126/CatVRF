-- ClickHouse schema for Unified Logistics Platform ML features
-- Схема для сбора данных обучения ML-моделей

-- Таблица заказов
CREATE TABLE IF NOT EXISTS logistics_orders ON CLUSTER '{cluster}' (
    tenant_id UInt64,
    order_id UInt64,
    user_id UInt64,
    created_at DateTime,
    delivery_lat Float64,
    delivery_lon Float64,
    weight_kg Float32,
    volume_cm3 Float32,
    items_count UInt16,
    total_amount UInt64,
    fulfillment_type LowCardinality(String),
    fulfillment_id UInt64,
    is_time_sensitive UInt8,
    prefers_pvz UInt8,
    district String,
    hour_of_day UInt8,
    day_of_week UInt8,
    is_weekend UInt8,
    correlation_id String,
    event_time DateTime
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(event_time)
ORDER BY (tenant_id, event_time, order_id)
TTL event_time + INTERVAL 90 DAY;

-- Таблица позиций курьеров
CREATE TABLE IF NOT EXISTS logistics_courier_locations ON CLUSTER '{cluster}' (
    tenant_id UInt64,
    courier_id UInt64,
    lat Float64,
    lon Float64,
    status LowCardinality(String),
    vehicle_type LowCardinality(String),
    is_taxi_driver UInt8,
    speed_kmh Float32,
    battery_level UInt8,
    event_time DateTime,
    correlation_id String
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(event_time)
ORDER BY (tenant_id, courier_id, event_time)
TTL event_time + INTERVAL 30 DAY;

-- Таблица назначений курьеров
CREATE TABLE IF NOT EXISTS logistics_courier_assignments ON CLUSTER '{cluster}' (
    tenant_id UInt64,
    shipment_id UInt64,
    order_id UInt64,
    courier_id UInt64,
    courier_vehicle_type LowCardinality(String),
    courier_is_taxi UInt8,
    courier_rating Float32,
    courier_capacity_kg Float32,
    distance_m UInt32,
    eta_predicted UInt16,
    eta_actual UInt16,
    eta_error_minutes Int16,
    assignment_time DateTime,
    accepted UInt8,
    rejected UInt8,
    rejection_reason String,
    hour_of_day UInt8,
    day_of_week UInt8,
    weather_condition LowCardinality(String),
    traffic_level LowCardinality(String),
    correlation_id String,
    event_time DateTime
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(event_time)
ORDER BY (tenant_id, shipment_id, event_time)
TTL event_time + INTERVAL 180 DAY;

-- Таблица назначений ПВЗ
CREATE TABLE IF NOT EXISTS logistics_pvz_assignments ON CLUSTER '{cluster}' (
    tenant_id UInt64,
    shipment_id UInt64,
    order_id UInt64,
    pvz_id UInt64,
    pvz_name String,
    pvz_lat Float64,
    pvz_lon Float64,
    distance_m UInt32,
    pvz_capacity UInt16,
    pvz_load_before UInt16,
    pvz_load_after UInt16,
    pvz_is_24h UInt8,
    pvz_working_hours String,
    user_preference_score Float32,
    pickup_code String,
    issued_at_pvz DateTime,
    collected_at DateTime,
    time_to_collect_minutes UInt16,
    correlation_id String,
    event_time DateTime
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(event_time)
ORDER BY (tenant_id, shipment_id, event_time)
TTL event_time + INTERVAL 180 DAY;

-- Таблица сравнения ETA
CREATE TABLE IF NOT EXISTS logistics_eta_comparisons ON CLUSTER '{cluster}' (
    tenant_id UInt64,
    shipment_id UInt64,
    order_id UInt64,
    courier_id UInt64,
    vehicle_type LowCardinality(String),
    predicted_eta_minutes UInt16,
    actual_eta_minutes UInt16,
    error_minutes Int16,
    mape Float32,
    hour_of_day UInt8,
    day_of_week UInt8,
    weather_condition LowCardinality(String),
    traffic_level LowCardinality(String),
    distance_km Float32,
    model_version String,
    correlation_id String,
    event_time DateTime
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(event_time)
ORDER BY (tenant_id, shipment_id, event_time)
TTL event_time + INTERVAL 365 DAY;

-- Таблица загрузки ПВЗ
CREATE TABLE IF NOT EXISTS logistics_pvz_loads ON CLUSTER '{cluster}' (
    tenant_id UInt64,
    pvz_id UInt64,
    pvz_name String,
    capacity UInt16,
    current_load UInt16,
    load_percentage Float32,
    is_overloaded UInt8,
    hour_of_day UInt8,
    day_of_week UInt8,
    district String,
    correlation_id String,
    event_time DateTime
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(event_time)
ORDER BY (tenant_id, pvz_id, event_time)
TTL event_time + INTERVAL 90 DAY;

-- Таблица отмен и возвратов
CREATE TABLE IF NOT EXISTS logistics_cancellations ON CLUSTER '{cluster}' (
    tenant_id UInt64,
    shipment_id UInt64,
    order_id UInt64,
    fulfillment_type LowCardinality(String),
    fulfillment_id UInt64,
    cancellation_reason LowCardinality(String),
    cancellation_time DateTime,
    time_since_assignment_minutes UInt16,
    user_id UInt64,
    courier_id UInt64,
    was_accepted UInt8,
    was_in_transit UInt8,
    hour_of_day UInt8,
    day_of_week UInt8,
    correlation_id String,
    event_time DateTime
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(event_time)
ORDER BY (tenant_id, shipment_id, event_time)
TTL event_time + INTERVAL 365 DAY;

-- Материализованное представление для feature extraction (courier assignment)
CREATE MATERIALIZED VIEW IF NOT EXISTS logistics_courier_features_mv ON CLUSTER '{cluster}'
ENGINE = AggregatingMergeTree()
PARTITION BY toYYYYMM(event_time)
ORDER BY (tenant_id, courier_id, toStartOfHour(event_time))
AS SELECT
    tenant_id,
    courier_id,
    toStartOfHour(event_time) as hour,
    count() as total_assignments,
    avg(eta_error_minutes) as avg_eta_error,
    avg(distance_m) as avg_distance,
    countIf(accepted = 1) as accepted_count,
    countIf(rejected = 1) as rejected_count,
    avg(courier_rating) as avg_rating
FROM logistics_courier_assignments
GROUP BY tenant_id, courier_id, toStartOfHour(event_time);

-- Материализованное представление для PVZ demand forecasting
CREATE MATERIALIZED VIEW IF NOT EXISTS logistics_pvz_demand_mv ON CLUSTER '{cluster}'
ENGINE = AggregatingMergeTree()
PARTITION BY toYYYYMM(event_time)
ORDER BY (tenant_id, pvz_id, toStartOfHour(event_time))
AS SELECT
    tenant_id,
    pvz_id,
    toStartOfHour(event_time) as hour,
    count() as total_assignments,
    avg(distance_m) as avg_distance,
    avg(time_to_collect_minutes) as avg_time_to_collect,
    countIf(time_to_collect_minutes <= 60) as fast_pickup_count
FROM logistics_pvz_assignments
GROUP BY tenant_id, pvz_id, toStartOfHour(event_time);
