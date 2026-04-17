-- FraudML Feature Store - Online Features Table
-- ClickHouse table for offline training data
-- Serves as single source of truth for feature consistency

CREATE TABLE IF NOT EXISTS fraud_features_online
(
    entity_type String,           -- 'user', 'tenant', 'operation'
    entity_id String,             -- ID of the entity
    features_json String,         -- JSON-encoded features
    timestamp DateTime,           -- When features were computed
    correlation_id String,        -- Request correlation ID
    created_at DateTime DEFAULT now()
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(timestamp)
ORDER BY (entity_type, entity_id, timestamp)
TTL timestamp + INTERVAL 90 DAY;  -- Keep 90 days for training

-- Create materialized view for training dataset
CREATE MATERIALIZED VIEW IF NOT EXISTS fraud_features_training_mv
ENGINE = AggregatingMergeTree()
PARTITION BY toYYYYMM(timestamp)
ORDER BY (entity_type, toDate(timestamp))
AS SELECT
    entity_type,
    toDate(timestamp) as feature_date,
    count() as feature_count,
    groupUniqArray(entity_id) as unique_entities
FROM fraud_features_online
GROUP BY entity_type, feature_date;

-- Create index for fast lookups
CREATE INDEX IF NOT EXISTS idx_entity_lookup 
ON fraud_features_online (entity_type, entity_id) 
TYPE minmax GRANULARITY 1;
