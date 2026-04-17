<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Create Materialized Views for quota aggregation
     * 
     * Views provide real-time aggregates for:
     * - Hourly usage per tenant, vertical, resource_type
     * - Daily usage per tenant, vertical, resource_type
     * - Enables fast quota checks without scanning raw events
     */
    public function up(): void
    {
        try {
            $clickHouse = DB::connection('clickhouse');

            // Hourly aggregate view
            $clickHouse->statement("
                CREATE MATERIALIZED VIEW IF NOT EXISTS tenant_quota_usage_hourly_mv
                ENGINE = SummingMergeTree()
                PARTITION BY toYYYYMM(event_date)
                ORDER BY (tenant_id, event_date, event_hour, vertical_code, resource_type)
                AS SELECT
                    tenant_id,
                    business_group_id,
                    vertical_code,
                    resource_type,
                    toDate(event_timestamp) as event_date,
                    toHour(event_timestamp) as event_hour,
                    sum(amount_used) as total_amount_used,
                    count() as event_count,
                    count(DISTINCT user_id) as unique_users,
                    min(event_timestamp) as first_event_at,
                    max(event_timestamp) as last_event_at
                FROM tenant_quota_usage_log
                GROUP BY tenant_id, business_group_id, vertical_code, resource_type, event_date, event_hour
            ");

            // Daily aggregate view
            $clickHouse->statement("
                CREATE MATERIALIZED VIEW IF NOT EXISTS tenant_quota_usage_daily_mv
                ENGINE = SummingMergeTree()
                PARTITION BY toYYYYMM(event_date)
                ORDER BY (tenant_id, event_date, vertical_code, resource_type)
                AS SELECT
                    tenant_id,
                    business_group_id,
                    vertical_code,
                    resource_type,
                    toDate(event_timestamp) as event_date,
                    sum(amount_used) as total_amount_used,
                    count() as event_count,
                    count(DISTINCT user_id) as unique_users,
                    min(event_timestamp) as first_event_at,
                    max(event_timestamp) as last_event_at
                FROM tenant_quota_usage_log
                GROUP BY tenant_id, business_group_id, vertical_code, resource_type, event_date
            ");

            // Current hour aggregate (for real-time quota checks)
            $clickHouse->statement("
                CREATE MATERIALIZED VIEW IF NOT EXISTS tenant_quota_usage_current_hour_mv
                ENGINE = SummingMergeTree()
                ORDER BY (tenant_id, vertical_code, resource_type)
                AS SELECT
                    tenant_id,
                    business_group_id,
                    vertical_code,
                    resource_type,
                    toStartOfHour(event_timestamp) as hour_start,
                    sum(amount_used) as total_amount_used,
                    count() as event_count
                FROM tenant_quota_usage_log
                WHERE event_timestamp >= toStartOfHour(now())
                GROUP BY tenant_id, business_group_id, vertical_code, resource_type, hour_start
            ");

            // Target table for daily aggregates (for faster queries)
            $clickHouse->statement("
                CREATE TABLE IF NOT EXISTS tenant_quota_usage_daily (
                    tenant_id UInt64,
                    business_group_id UInt64,
                    vertical_code LowCardinality(String),
                    resource_type LowCardinality(String),
                    event_date Date,
                    total_amount_used Float64,
                    event_count UInt64,
                    unique_users UInt64,
                    first_event_at DateTime,
                    last_event_at DateTime,
                    created_at DateTime DEFAULT now()
                )
                ENGINE = SummingMergeTree()
                PARTITION BY toYYYYMM(event_date)
                ORDER BY (tenant_id, event_date, vertical_code, resource_type)
                TTL event_date + INTERVAL 365 DAY
            ");

        } catch (\Exception $e) {
            if (app()->environment('local', 'testing')) {
                return;
            }
            throw $e;
        }
    }

    public function down(): void
    {
        try {
            $clickHouse = DB::connection('clickhouse');
            $clickHouse->statement('DROP TABLE IF EXISTS tenant_quota_usage_current_hour_mv');
            $clickHouse->statement('DROP TABLE IF EXISTS tenant_quota_usage_daily_mv');
            $clickHouse->statement('DROP TABLE IF EXISTS tenant_quota_usage_hourly_mv');
            $clickHouse->statement('DROP TABLE IF EXISTS tenant_quota_usage_daily');
        } catch (\Exception $e) {
            // Ignore errors during rollback
        }
    }
};
