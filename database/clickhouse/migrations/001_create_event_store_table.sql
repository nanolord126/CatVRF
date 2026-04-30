-- Event Store for Medical and Financial Events in ClickHouse
-- Provides long-term storage and analytics for compliance and audit

CREATE TABLE IF NOT EXISTS event_store (
    -- Event identification
    event_id UUID DEFAULT generateUUIDv4(),
    event_type String,
    event_name String,
    
    -- Event data
    payload String, -- JSON serialized
    payload_hash String, -- SHA256 for deduplication
    
    -- Correlation and causation
    correlation_id String,
    causation_id Nullable(String),
    
    -- Context
    user_id Nullable(UInt64),
    tenant_id Nullable(UInt64),
    vertical String,
    
    -- Classification
    is_medical Bool DEFAULT false,
    is_financial Bool DEFAULT false,
    is_emergency Bool DEFAULT false,
    data_classification String DEFAULT 'public', -- public, internal, confidential, pii, medical
    
    -- Timing
    occurred_at DateTime64(3),
    published_at DateTime64(3),
    processed_at Nullable(DateTime64(3)),
    
    -- Metadata
    source String, -- outbox, direct, external
    publisher String,
    processing_time_ms Nullable(UInt32),
    
    -- Status
    status String DEFAULT 'published', -- published, processed, failed
    
    -- Partitioning by date for efficient queries
    created_at DateTime DEFAULT now()
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(created_at)
ORDER BY (event_type, created_at, event_id)
TTL created_at + INTERVAL 90 DAY -- Retain for 90 days
SETTINGS index_granularity = 8192;

-- Create indexes for common queries
CREATE INDEX IF NOT EXISTS idx_event_type ON event_store (event_type) TYPE bloom_filter GRANULARITY 1;
CREATE INDEX IF NOT EXISTS idx_vertical ON event_store (vertical) TYPE bloom_filter GRANULARITY 1;
CREATE INDEX IF NOT EXISTS idx_user_id ON event_store (user_id) TYPE bloom_filter GRANULARITY 1;
CREATE INDEX IF NOT EXISTS idx_correlation_id ON event_store (correlation_id) TYPE bloom_filter GRANULARITY 1;
CREATE INDEX IF NOT EXISTS idx_medical ON event_store (is_medical) TYPE set(1) GRANULARITY 1;
CREATE INDEX IF NOT EXISTS idx_financial ON event_store (is_financial) TYPE set(1) GRANULARITY 1;
CREATE INDEX IF NOT EXISTS idx_emergency ON event_store (is_emergency) TYPE set(1) GRANULARITY 1;
CREATE INDEX IF NOT EXISTS idx_status ON event_store (status) TYPE bloom_filter GRANULARITY 1;

-- Materialized view for recent events (last 24h)
CREATE MATERIALIZED VIEW IF NOT EXISTS event_store_24h
ENGINE = MergeTree()
PARTITION BY toYYYYMM(created_at)
ORDER BY (event_type, created_at)
AS SELECT *
FROM event_store
WHERE created_at >= now() - INTERVAL 1 DAY;

-- Aggregated view for event statistics
CREATE MATERIALIZED VIEW IF NOT EXISTS event_store_stats
ENGINE = SummingMergeTree()
PARTITION BY toYYYYMM(created_at)
ORDER BY (event_type, vertical, toYYYYMMDD(created_at))
AS SELECT
    event_type,
    vertical,
    toYYYYMMDD(created_at) as date,
    count() as event_count,
    countIf(is_medical) as medical_count,
    countIf(is_financial) as financial_count,
    countIf(is_emergency) as emergency_count,
    avg(processing_time_ms) as avg_processing_time_ms
FROM event_store
GROUP BY event_type, vertical, date;

-- Medical events specific table with additional PII tracking
CREATE TABLE IF NOT EXISTS event_store_medical
(
    event_id UUID,
    event_type String,
    payload String,
    correlation_id String,
    user_id Nullable(UInt64),
    patient_id Nullable(UInt64), -- Medical specific
    medical_record_id Nullable(UInt64),
    occurred_at DateTime64(3),
    created_at DateTime DEFAULT now(),
    pii_masked Bool DEFAULT true,
    pii_fields Array(String) -- List of fields that were masked
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(created_at)
ORDER BY (patient_id, created_at, event_id)
TTL created_at + INTERVAL 365 DAY -- Retain for 1 year for medical compliance
SETTINGS index_granularity = 8192;

-- Financial events specific table with additional audit fields
CREATE TABLE IF NOT EXISTS event_store_financial
(
    event_id UUID,
    event_type String,
    payload String,
    correlation_id String,
    user_id Nullable(UInt64),
    account_id Nullable(UInt64),
    transaction_id Nullable(String),
    amount Nullable(Decimal(18, 2)),
    currency String,
    occurred_at DateTime64(3),
    created_at DateTime DEFAULT now(),
    requires_audit Bool DEFAULT false,
    audit_status Nullable(String), -- pending, approved, rejected
    audit_user_id Nullable(UInt64)
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(created_at)
ORDER BY (transaction_id, created_at, event_id)
TTL created_at + INTERVAL 730 DAY -- Retain for 2 years for financial compliance
SETTINGS index_granularity = 8192;
