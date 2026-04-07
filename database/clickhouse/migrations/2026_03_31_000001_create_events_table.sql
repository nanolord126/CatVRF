CREATE TABLE default.events (
    `uuid` UUID,
    `tenant_id` UInt64,
    `user_id` Nullable(UInt64),
    `event_type` String,
    `payload` String,
    `vertical` String,
    `ip_address` Nullable(IPv4),
    `device_fingerprint` Nullable(String),
    `created_at` DateTime,
    `correlation_id` UUID
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(created_at)
ORDER BY (tenant_id, event_type, created_at);
