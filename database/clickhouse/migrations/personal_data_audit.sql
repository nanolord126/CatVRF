-- Personal Data Audit Log (152-FZ + ФСТЭК №21 Compliance)
-- Immutable audit trail for all personal data access
-- ФСТЭК №21: Мера 4 - Регистрация и учёт действий
-- 
-- PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security

-- Drop table if exists (for idempotent migration)
DROP TABLE IF EXISTS personal_data_audit;

-- Create MergeTree table for immutable audit log
CREATE TABLE IF NOT EXISTS personal_data_audit ON CLUSTER '{cluster}' (
    event_id UUID DEFAULT generateUUIDv4(),
    event_type LowCardinality(String),
    target_user_id UInt64,
    target_user_uuid UUID,
    accessor_user_id UInt64,
    data_type LowCardinality(String),
    action LowCardinality(String),
    purpose String,
    ip_address IPv4,
    user_agent String,
    timestamp DateTime64(3, 'UTC'),
    correlation_id String,
    requires_jit_access Bool,
    metadata String,
    tenant_id UInt64,
    created_at DateTime64(3, 'UTC') DEFAULT now64(3, 'UTC')
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(timestamp)
ORDER BY (timestamp, target_user_id, accessor_user_id, event_type)
TTL timestamp + INTERVAL 7 YEAR
SETTINGS index_granularity = 8192;

-- Create materialized view for insider threat detection
DROP TABLE IF EXISTS personal_data_audit_insider_mv;

CREATE MATERIALIZED VIEW IF NOT EXISTS personal_data_audit_insider_mv ON CLUSTER '{cluster}'
TO insider_threat_monitoring AS
SELECT
    event_id,
    accessor_user_id,
    target_user_id,
    data_type,
    action,
    ip_address,
    user_agent,
    timestamp,
    tenant_id,
    correlation_id,
    metadata
FROM personal_data_audit
WHERE event_type = 'personal_data_access';

-- Create materialized view for biometric data collection monitoring
DROP TABLE IF EXISTS biometric_collection_monitoring_mv;

CREATE MATERIALIZED VIEW IF NOT EXISTS biometric_collection_monitoring_mv ON CLUSTER '{cluster}'
TO biometric_collection_monitoring AS
SELECT
    event_id,
    user_id AS target_user_id,
    user_uuid AS target_user_uuid,
    biometric_type AS data_type,
    context AS action,
    ip_address,
    user_agent,
    timestamp,
    correlation_id
FROM personal_data_audit
WHERE event_type = 'biometric_data_collected';

-- Create aggregated view for daily access statistics
DROP TABLE IF EXISTS personal_data_access_daily;

CREATE TABLE IF NOT EXISTS personal_data_access_daily ON CLUSTER '{cluster}' (
    date Date,
    accessor_user_id UInt64,
    data_type LowCardinality(String),
    action LowCardinality(String),
    access_count UInt64,
    unique_users_accessed UInt64,
    tenant_id UInt64
) ENGINE = SummingMergeTree()
PARTITION BY toYYYYMM(date)
ORDER BY (date, accessor_user_id, data_type, action, tenant_id)
TTL date + INTERVAL 2 YEAR;

-- Create materialized view for daily aggregation
DROP TABLE IF EXISTS personal_data_access_daily_mv;

CREATE MATERIALIZED VIEW IF NOT EXISTS personal_data_access_daily_mv ON CLUSTER '{cluster}'
TO personal_data_access_daily AS
SELECT
    toDate(timestamp) AS date,
    accessor_user_id,
    data_type,
    action,
    count() AS access_count,
    uniqExact(target_user_id) AS unique_users_accessed,
    tenant_id
FROM personal_data_audit
WHERE event_type = 'personal_data_access'
GROUP BY
    date,
    accessor_user_id,
    data_type,
    action,
    tenant_id;

-- Create table for insider threat monitoring (PostgreSQL fallback support)
-- This table mirrors the ClickHouse structure for PostgreSQL
DROP TABLE IF EXISTS insider_threat_monitoring;

CREATE TABLE IF NOT EXISTS insider_threat_monitoring (
    id BIGSERIAL PRIMARY KEY,
    accessor_user_id BIGINT NOT NULL,
    target_user_id BIGINT NOT NULL,
    data_type VARCHAR(50) NOT NULL,
    action VARCHAR(20) NOT NULL,
    ip_address INET,
    user_agent TEXT,
    timestamp TIMESTAMP WITH TIME ZONE NOT NULL,
    tenant_id BIGINT,
    correlation_id VARCHAR(64),
    metadata JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_insider_monitoring_accessor_timestamp ON insider_threat_monitoring(accessor_user_id, timestamp);
CREATE INDEX idx_insider_monitoring_target_timestamp ON insider_threat_monitoring(target_user_id, timestamp);
CREATE INDEX idx_insider_monitoring_data_type ON insider_threat_monitoring(data_type);
CREATE INDEX idx_insider_monitoring_tenant ON insider_threat_monitoring(tenant_id);
CREATE INDEX idx_insider_monitoring_correlation ON insider_threat_monitoring(correlation_id);

-- Create table for biometric collection monitoring
DROP TABLE IF EXISTS biometric_collection_monitoring;

CREATE TABLE IF NOT EXISTS biometric_collection_monitoring (
    id BIGSERIAL PRIMARY KEY,
    event_id UUID NOT NULL,
    target_user_id BIGINT NOT NULL,
    target_user_uuid UUID NOT NULL,
    data_type VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    ip_address INET,
    user_agent TEXT,
    timestamp TIMESTAMP WITH TIME ZONE NOT NULL,
    correlation_id VARCHAR(64),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_biometric_monitoring_user_timestamp ON biometric_collection_monitoring(target_user_id, timestamp);
CREATE INDEX idx_biometric_monitoring_type ON biometric_collection_monitoring(data_type);
CREATE INDEX idx_biometric_monitoring_correlation ON biometric_collection_monitoring(correlation_id);
