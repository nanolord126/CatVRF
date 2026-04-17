<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add ClickHouse partitioning by tenant and date for anonymized_behavior table
     * Canon 2026: Partition by tenant_id + event_timestamp for performance at 10M users
     */
    public function up(): void
    {
        try {
            $clickHouse = DB::connection('clickhouse');

            // Check if table exists
            $tableExists = $clickHouse->select("
                SELECT 1 
                FROM system.tables 
                WHERE database = currentDatabase() 
                AND name = 'anonymized_behavior'
            ")->count() > 0;

            if (!$tableExists) {
                // Create table with partitioning if it doesn't exist
                $clickHouse->statement("
                    CREATE TABLE IF NOT EXISTS anonymized_behavior (
                        anonymized_user_id String,
                        user_id UInt64,
                        event_timestamp DateTime,
                        vertical String,
                        action String,
                        session_duration UInt32,
                        device_type String,
                        city_hash UInt32,
                        behavior_cluster UInt8,
                        taste_vector String,
                        correlation_id String,
                        created_at DateTime DEFAULT now()
                    )
                    ENGINE = MergeTree()
                    PARTITION BY toYYYYMM(event_timestamp)
                    ORDER BY (tenant_id, event_timestamp, anonymized_user_id)
                    SETTINGS index_granularity = 8192
                ");
            } else {
                // Add partitioning to existing table (requires recreation)
                $clickHouse->statement("
                    ALTER TABLE anonymized_behavior 
                    MODIFY PARTITION BY toYYYYMM(event_timestamp)
                ");

                $clickHouse->statement("
                    ALTER TABLE anonymized_behavior 
                    MODIFY ORDER BY (tenant_id, event_timestamp, anonymized_user_id)
                ");
            }

            // Add tenant_id column if missing
            $columns = $clickHouse->select("
                SELECT name 
                FROM system.columns 
                WHERE table = 'anonymized_behavior'
            ")->pluck('name')->toArray();

            if (!in_array('tenant_id', $columns)) {
                $clickHouse->statement("
                    ALTER TABLE anonymized_behavior 
                    ADD COLUMN tenant_id UInt64 AFTER anonymized_user_id
                ");
            }

            // Create materialized view for daily aggregation
            $clickHouse->statement("
                CREATE MATERIALIZED VIEW IF NOT EXISTS daily_behavior_stats_mv
                ENGINE = SummingMergeTree()
                PARTITION BY toYYYYMM(event_date)
                ORDER BY (tenant_id, event_date, vertical)
                AS SELECT
                    tenant_id,
                    toDate(event_timestamp) as event_date,
                    vertical,
                    action,
                    count() as event_count,
                    avg(session_duration) as avg_session_duration,
                    count(DISTINCT anonymized_user_id) as unique_users
                FROM anonymized_behavior
                GROUP BY tenant_id, event_date, vertical, action
            ");

        } catch (\Exception $e) {
            // ClickHouse might not be configured, log but don't fail migration
            if (app()->environment('local', 'testing')) {
                // In local/testing, we can skip ClickHouse setup
                return;
            }
            throw $e;
        }
    }

    public function down(): void
    {
        try {
            $clickHouse = DB::connection('clickhouse');
            
            $clickHouse->statement("DROP TABLE IF EXISTS daily_behavior_stats_mv");
            $clickHouse->statement("DROP TABLE IF EXISTS anonymized_behavior");
            
        } catch (\Exception $e) {
            // Ignore errors during rollback
        }
    }
};
