-- ClickHouse Analytics Schema
-- Phase 3A: Time-Series Heatmap Integration
-- Created: March 24, 2026

-- Drop existing tables (for fresh setup)
DROP TABLE IF EXISTS ch_geo_events ON CLUSTER default;
DROP TABLE IF EXISTS ch_click_events ON CLUSTER default;
DROP TABLE IF EXISTS ch_geo_hourly ON CLUSTER default;
DROP TABLE IF EXISTS ch_click_hourly ON CLUSTER default;

-- Main Event Tables

CREATE TABLE ch_geo_events (
    -- Identifiers
    id UUID,
    tenant_id UInt32,
    vertical String,
    
    -- Event data
    event_type String,  -- 'view', 'click', 'scroll'
    latitude Float64,
    longitude Float64,
    geo_hash String,
    
    -- Context
    user_id Nullable(UInt32),
    session_id String,
    device_type Enum8('mobile' = 1, 'desktop' = 2, 'tablet' = 3, 'unknown' = 4),
    browser String,
    country_code String,
    
    -- Metadata
    created_at DateTime,
    correlation_id String,
    
    -- Indices for performance
    INDEX idx_tenant_created (tenant_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_geo_hash (geo_hash) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_created_at (created_at) TYPE minmax GRANULARITY 1024,
    
    COMMENT 'Geo activity events from Phase 1-2B'
) ENGINE = MergeTree()
ORDER BY (tenant_id, created_at, vertical)
PARTITION BY toYYYYMMDD(created_at)
TTL created_at + INTERVAL 730 DAY;

CREATE TABLE ch_click_events (
    -- Identifiers
    id UUID,
    tenant_id UInt32,
    vertical String,
    
    -- Click data
    page_url String,
    x_coordinate UInt16,
    y_coordinate UInt16,
    click_duration_ms UInt32,
    element_selector Nullable(String),
    element_type Enum8(
        'button' = 1,
        'link' = 2,
        'image' = 3,
        'text' = 4,
        'form' = 5,
        'input' = 6,
        'other' = 7
    ),
    
    -- Context
    user_id Nullable(UInt32),
    session_id String,
    device_type Enum8('mobile' = 1, 'desktop' = 2, 'tablet' = 3, 'unknown' = 4),
    viewport_width UInt16,
    viewport_height UInt16,
    
    -- Metadata
    created_at DateTime,
    correlation_id String,
    
    -- Indices
    INDEX idx_tenant_created (tenant_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_page_url (page_url) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_coordinates (x_coordinate, y_coordinate) TYPE minmax GRANULARITY 1024,
    
    COMMENT 'Click heat map events from Phase 1-2B'
) ENGINE = MergeTree()
ORDER BY (tenant_id, created_at, page_url)
PARTITION BY toYYYYMMDD(created_at)
TTL created_at + INTERVAL 730 DAY;

-- Aggregated Tables (SummingMergeTree for faster queries)

CREATE TABLE ch_geo_hourly (
    tenant_id UInt32,
    vertical String,
    geo_hash String,
    event_type String,
    device_type String,
    hour DateTime,
    
    event_count UInt64,
    unique_users UInt64,
    unique_sessions UInt64,
    
    COMMENT 'Hourly aggregation of geo events'
) ENGINE = SummingMergeTree()
ORDER BY (tenant_id, hour, geo_hash)
PARTITION BY toYYYYMM(hour);

CREATE TABLE ch_click_hourly (
    tenant_id UInt32,
    vertical String,
    page_url String,
    hour DateTime,
    device_type String,
    
    click_count UInt64,
    unique_users UInt64,
    
    COMMENT 'Hourly aggregation of click events'
) ENGINE = SummingMergeTree()
ORDER BY (tenant_id, hour, page_url)
PARTITION BY toYYYYMM(hour);

-- Materialized Views for Auto-Aggregation

CREATE MATERIALIZED VIEW ch_geo_hourly_mv TO ch_geo_hourly AS
SELECT
    tenant_id,
    vertical,
    geo_hash,
    event_type,
    device_type,
    toStartOfHour(created_at) AS hour,
    
    COUNT(*) AS event_count,
    uniq(user_id) AS unique_users,
    uniq(session_id) AS unique_sessions
FROM ch_geo_events
GROUP BY tenant_id, vertical, geo_hash, event_type, device_type, hour;

CREATE MATERIALIZED VIEW ch_click_hourly_mv TO ch_click_hourly AS
SELECT
    tenant_id,
    vertical,
    page_url,
    toStartOfHour(created_at) AS hour,
    device_type,
    
    COUNT(*) AS click_count,
    uniq(user_id) AS unique_users
FROM ch_click_events
GROUP BY tenant_id, vertical, page_url, hour, device_type;

-- System Settings

-- Ensure proper data durability
SET max_insert_threads = 4;
SET background_pool_size = 32;

-- Query cache settings
SET query_cache_max_size_in_bytes = 268435456;  -- 256MB
SET query_cache_ttl = 3600;                     -- 1 hour

-- Compression settings
SET compression_codec = 'LZ4';

-- Log Queries
SYSTEM FLUSH LOGS;

-- Health Check Query
SELECT 'ClickHouse Schema Installation Complete' AS status,
       now() AS created_at,
       version() AS clickhouse_version;
