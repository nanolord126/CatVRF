-- ClickHouse Security Audit Schema
-- CatVRF 2026 Database Security Fortress
-- Immutable audit tables for security events, hunting detection, and query monitoring

-- Drop existing tables (for fresh setup)
DROP TABLE IF EXISTS ch_security_audit ON CLUSTER default;
DROP TABLE IF EXISTS ch_query_audit ON CLUSTER default;
DROP TABLE IF EXISTS ch_hunting_detection ON CLUSTER default;
DROP TABLE IF EXISTS ch_cross_tenant_attempts ON CLUSTER default;

-- Main Security Audit Table (Immutable)
CREATE TABLE ch_security_audit (
    -- Identifiers
    id UUID,
    tenant_id UInt32,
    user_id UInt32,
    
    -- Event details
    event_type String,  -- 'sql_injection', 'cross_tenant_query', 'hunting_pattern', 'export_denied', etc.
    event_category String,  -- 'sql_injection', 'data_exfiltration', 'access_control', 'fraud'
    severity Enum8('low' = 1, 'medium' = 2, 'high' = 3, 'critical' = 4),
    
    -- Query details (if applicable)
    query_sql String,
    query_hash String,
    rows_returned UInt32,
    execution_time_ms UInt32,
    
    -- Context
    ip_address String,
    user_agent String,
    device_fingerprint String,
    session_id String,
    
    -- Metadata
    correlation_id String,
    metadata JSON,
    
    -- Timestamps
    created_at DateTime,
    
    -- Indices for performance
    INDEX idx_tenant_created (tenant_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_user_created (user_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_event_type (event_type) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_severity (severity) TYPE set(4) GRANULARITY 8192,
    INDEX idx_ip_address (ip_address) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_correlation_id (correlation_id) TYPE bloom_filter GRANULARITY 8192,
    
    COMMENT 'Immutable security audit log for all security events'
) ENGINE = MergeTree()
ORDER BY (tenant_id, created_at, event_type)
PARTITION BY toYYYYMMDD(created_at)
TTL created_at + INTERVAL 90 DAY;

-- Query Audit Table (for SQL injection and pattern detection)
CREATE TABLE ch_query_audit (
    -- Identifiers
    id UUID,
    tenant_id UInt32,
    user_id UInt32,
    
    -- Query details
    query_sql String,
    query_hash String,
    query_type Enum8('select' = 1, 'insert' = 2, 'update' = 3, 'delete' = 4, 'other' = 5),
    table_name String,
    
    -- Query patterns
    has_like Boolean,
    has_or_where Boolean,
    has_tenant_id_filter Boolean,
    has_limit Boolean,
    limit_value UInt32,
    
    -- Performance
    execution_time_ms UInt32,
    rows_returned UInt32,
    rows_affected UInt32,
    
    -- Context
    ip_address String,
    user_agent String,
    correlation_id String,
    
    -- Timestamps
    created_at DateTime,
    
    -- Indices
    INDEX idx_tenant_created (tenant_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_user_created (user_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_query_hash (query_hash) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_table_name (table_name) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_patterns (has_like, has_or_where, has_tenant_id_filter) TYPE set(3) GRANULARITY 8192,
    
    COMMENT 'Query audit log for SQL injection and pattern detection'
) ENGINE = MergeTree()
ORDER BY (tenant_id, created_at, query_hash)
PARTITION BY toYYYYMMDD(created_at)
TTL created_at + INTERVAL 30 DAY;

-- Hunting Detection Table (for behavioral analysis)
CREATE TABLE ch_hunting_detection (
    -- Identifiers
    id UUID,
    tenant_id UInt32,
    user_id UInt32,
    
    -- Hunting metrics
    hunting_score Float32,
    frequency_score Float32,
    pattern_score Float32,
    result_size_score Float32,
    time_anomaly_score Float32,
    
    -- Query patterns
    query_count UInt32,
    like_query_count UInt32,
    or_where_count UInt32,
    total_rows_returned UInt32,
    
    -- Detection context
    detection_window_minutes UInt32,
    time_range_start DateTime,
    time_range_end DateTime,
    
    -- Action taken
    action_taken Enum8('logged' = 1, 'cooldown_triggered' = 2, 'blocked' = 3),
    cooldown_duration_hours UInt32,
    
    -- Context
    ip_address String,
    user_agent String,
    correlation_id String,
    
    -- Timestamps
    created_at DateTime,
    
    -- Indices
    INDEX idx_tenant_created (tenant_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_user_created (user_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_hunting_score (hunting_score) TYPE minmax GRANULARITY 8192,
    INDEX idx_action_taken (action_taken) TYPE set(3) GRANULARITY 8192,
    
    COMMENT 'Hunting detection metrics and behavioral analysis'
) ENGINE = MergeTree()
ORDER BY (tenant_id, created_at, hunting_score)
PARTITION BY toYYYYMMDD(created_at)
TTL created_at + INTERVAL 90 DAY;

-- Cross-Tenant Access Attempts Table
CREATE TABLE ch_cross_tenant_attempts (
    -- Identifiers
    id UUID,
    user_id UInt32,
    user_tenant_id UInt32,
    target_tenant_id UInt32,
    
    -- Attempt details
    attempt_type Enum8('query' = 1, 'api' = 2, 'filament' = 3, 'other' = 4),
    route String,
    table_name String,
    
    -- Context
    ip_address String,
    user_agent String,
    correlation_id String,
    
    -- Result
    blocked Boolean,
    block_reason String,
    
    -- Timestamps
    created_at DateTime,
    
    -- Indices
    INDEX idx_user_created (user_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_user_tenant (user_tenant_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_target_tenant (target_tenant_id) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_blocked (blocked) TYPE set(1) GRANULARITY 8192,
    
    COMMENT 'Cross-tenant access attempt monitoring'
) ENGINE = MergeTree()
ORDER BY (user_id, created_at, target_tenant_id)
PARTITION BY toYYYYMMDD(created_at)
TTL created_at + INTERVAL 180 DAY;

-- Materialized View: High-Risk Security Events (hourly aggregation)
CREATE MATERIALIZED VIEW ch_security_events_hourly_mv TO ch_security_events_hourly AS
SELECT
    toStartOfHour(created_at) AS hour,
    tenant_id,
    event_type,
    event_category,
    severity,
    COUNT(*) AS event_count,
    uniq(user_id) AS unique_users,
    uniq(ip_address) AS unique_ips,
    AVG(execution_time_ms) AS avg_execution_time_ms
FROM ch_security_audit
GROUP BY hour, tenant_id, event_type, event_category, severity;

CREATE TABLE ch_security_events_hourly (
    hour DateTime,
    tenant_id UInt32,
    event_type String,
    event_category String,
    severity Enum8('low' = 1, 'medium' = 2, 'high' = 3, 'critical' = 4),
    event_count UInt64,
    unique_users UInt64,
    unique_ips UInt64,
    avg_execution_time_ms Float32
) ENGINE = SummingMergeTree()
ORDER BY (hour, tenant_id, event_type)
PARTITION BY toYYYYMM(hour);

-- Materialized View: Hunting Detection Summary (daily aggregation)
CREATE MATERIALIZED VIEW ch_hunting_summary_daily_mv TO ch_hunting_summary_daily AS
SELECT
    toStartDate(created_at) AS date,
    tenant_id,
    COUNT(*) AS total_detections,
    COUNTIf(action_taken = 'cooldown_triggered') AS cooldowns_triggered,
    COUNTIf(action_taken = 'blocked') AS blocks_triggered,
    AVG(hunting_score) AS avg_hunting_score,
    MAX(hunting_score) AS max_hunting_score,
    uniq(user_id) AS unique_users_flagged
FROM ch_hunting_detection
GROUP BY date, tenant_id;

CREATE TABLE ch_hunting_summary_daily (
    date Date,
    tenant_id UInt32,
    total_detections UInt64,
    cooldowns_triggered UInt64,
    blocks_triggered UInt64,
    avg_hunting_score Float32,
    max_hunting_score Float32,
    unique_users_flagged UInt64
) ENGINE = SummingMergeTree()
ORDER BY (date, tenant_id)
PARTITION BY toYYYYMM(date);

-- Materialized View: Cross-Tenant Attempts Summary (daily aggregation)
CREATE MATERIALIZED VIEW ch_cross_tenant_daily_mv TO ch_cross_tenant_daily AS
SELECT
    toStartDate(created_at) AS date,
    user_tenant_id,
    COUNT(*) AS total_attempts,
    COUNTIf(blocked = true) AS blocked_attempts,
    uniq(user_id) AS unique_users_attempting,
    uniq(target_tenant_id) AS unique_target_tenants
FROM ch_cross_tenant_attempts
GROUP BY date, user_tenant_id;

CREATE TABLE ch_cross_tenant_daily (
    date Date,
    user_tenant_id UInt32,
    total_attempts UInt64,
    blocked_attempts UInt64,
    unique_users_attempting UInt64,
    unique_target_tenants UInt64
) ENGINE = SummingMergeTree()
ORDER BY (date, user_tenant_id)
PARTITION BY toYYYYMM(date);

-- System Settings for Security Monitoring
SET max_insert_threads = 4;
SET background_pool_size = 32;

-- Enable system query logging for additional monitoring
SET log_queries = 1;
SET log_queries_min_type = 2;  -- Log queries with type >= 2 (SELECT, INSERT, etc.)
SET log_queries_min_query_duration_ms = 100;  -- Log queries taking > 100ms

-- Health Check Query
SELECT 'ClickHouse Security Audit Schema Installation Complete' AS status,
       now() AS created_at,
       version() AS clickhouse_version;
