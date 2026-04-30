-- Bot Detection Events Table
-- ClickHouse migration for bot detection logging
-- Created: April 23, 2026
-- Purpose: Store all bot detection events for analytics and monitoring

DROP TABLE IF EXISTS bot_detection_events ON CLUSTER default;

CREATE TABLE bot_detection_events (
    -- Identifiers
    id UUID DEFAULT generateUUIDv4(),
    correlation_id String,
    
    -- Detection result
    is_bot Bool,
    risk_level Enum8('low' = 1, 'medium' = 2, 'high' = 3, 'critical' = 4),
    confidence Float32,
    
    -- Request context
    ip_address String,
    user_agent String,
    request_method String,
    request_path String,
    
    -- User context (anonymized)
    user_hash Nullable(String),
    tenant_id Nullable(UInt32),
    session_id String,
    
    -- Detection signals
    detection_sources Array(String),
    detection_signals Array(String),
    matched_rule Nullable(String),
    
    -- Integration metadata
    vpn_detected Nullable(Bool),
    vpn_risk_level Nullable(String),
    behavioral_score Nullable(Float32),
    fraud_score Nullable(Float32),
    
    -- Russian territory flags
    is_russian_territory Nullable(Bool),
    territory_name Nullable(String),
    
    -- Protection applied
    protection_applied Nullable(String),
    was_blocked Nullable(Bool),
    was_challenged Nullable(Bool),
    
    -- Timing
    detection_duration_ms UInt32,
    created_at DateTime DEFAULT now(),
    
    -- Indices for performance
    INDEX idx_risk_created (risk_level, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_ip_created (ip_address, created_at) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_user_hash (user_hash) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_tenant_created (tenant_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_sources (detection_sources) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_correlation (correlation_id) TYPE bloom_filter GRANULARITY 8192,
    
    COMMENT 'Bot detection events for security monitoring and analytics'
) ENGINE = MergeTree()
ORDER BY (created_at, risk_level, ip_address)
PARTITION BY toYYYYMMDD(created_at)
TTL created_at + INTERVAL 90 DAY;

-- Create aggregated table for daily statistics
DROP TABLE IF EXISTS bot_detection_daily_stats ON CLUSTER default;

CREATE TABLE bot_detection_daily_stats (
    date Date,
    risk_level Enum8('low' = 1, 'medium' = 2, 'high' = 3, 'critical' = 4),
    
    -- Counts
    total_detections UInt64,
    unique_ips UInt64,
    unique_user_hashes UInt64,
    
    -- Top sources
    top_detection_sources Array(String),
    top_matched_rules Array(String),
    
    -- Protection metrics
    blocked_count UInt64,
    challenged_count UInt64,
    
    -- Territory metrics
    russian_territory_count UInt64,
    
    COMMENT 'Daily aggregated bot detection statistics'
) ENGINE = SummingMergeTree()
ORDER BY (date, risk_level)
PARTITION BY toYYYYMM(date);

-- Create materialized view for auto-aggregation
DROP MATERIALIZED VIEW IF EXISTS bot_detection_daily_stats_mv ON CLUSTER default;

CREATE MATERIALIZED VIEW bot_detection_daily_stats_mv TO bot_detection_daily_stats AS
SELECT
    toDate(created_at) AS date,
    risk_level,
    
    COUNT(*) AS total_detections,
    uniq(ip_address) AS unique_ips,
    uniq(user_hash) AS unique_user_hashes,
    
    groupUniqArrayArray(detection_sources) AS top_detection_sources,
    groupUniqArrayArray(matched_rule) AS top_matched_rules,
    
    countIf(was_blocked = 1) AS blocked_count,
    countIf(was_challenged = 1) AS challenged_count,
    
    countIf(is_russian_territory = 1) AS russian_territory_count
FROM bot_detection_events
GROUP BY date, risk_level;

-- Health check
SELECT 'Bot Detection Events Table Created Successfully' AS status,
       now() AS created_at;
