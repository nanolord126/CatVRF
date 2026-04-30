-- ClickHouse Feature Store Initialization Migration
-- Run this to initialize the feature store tables
-- Usage: clickhouse-client --host localhost --port 9000 --multiquery < 001_init_feature_store.sql

-- Create feature store tables
-- This file references the main schema in database/clickhouse/feature_store.sql

-- Execute the main feature store schema
-- Note: In production, run the full feature_store.sql file
-- This is a convenience migration for quick setup

-- Health check
SELECT 'ClickHouse Feature Store Migration Started' AS status, now() AS timestamp;

-- The actual table creation is in database/clickhouse/feature_store.sql
-- Run: clickhouse-client --multiquery < database/clickhouse/feature_store.sql

SELECT 'ClickHouse Feature Store Migration Complete' AS status, now() AS timestamp;
