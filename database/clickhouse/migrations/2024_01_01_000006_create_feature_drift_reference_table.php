<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ClickHouseDB\Schema AS ClickHouseSchema;

/**
 * ClickHouse migration: Create feature drift reference distributions table
 * 
 * Stores reference feature distributions for drift detection.
 * Supports per-vertical and per-model-version tracking.
 */
return new class
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $db = \ClickHouseDB::getInstance();

        // Create main table for reference distributions
        $db->write('
            CREATE TABLE IF NOT EXISTS feature_drift_reference (
                model_version String,
                feature_name String,
                vertical_code Nullable(String),
                bin_value String,
                bin_count UInt32,
                total_count UInt32,
                bin_probability Float64,
                created_at DateTime64(3),
                updated_at DateTime64(3)
            )
            ENGINE = ReplacingMergeTree(updated_at)
            PARTITION BY toYYYYMM(created_at)
            ORDER BY (model_version, feature_name, vertical_code, bin_value)
            TTL created_at + INTERVAL 90 DAY
        ');

        // Create materialized view for aggregating current feature distributions
        $db->write('
            CREATE MATERIALIZED VIEW IF NOT EXISTS feature_drift_current_mv
            ENGINE = AggregatingMergeTree()
            PARTITION BY toYYYYMM(created_at)
            ORDER BY (model_version, feature_name, vertical_code, bin_value)
            AS SELECT
                model_version,
                feature_name,
                vertical_code,
                bin_value,
                sumState(bin_count) as bin_count,
                sumState(total_count) as total_count,
                avgState(bin_probability) as bin_probability,
                max(created_at) as created_at,
                now() as updated_at
            FROM feature_drift_reference
            GROUP BY model_version, feature_name, vertical_code, bin_value
        ');

        // Create table for drift detection results
        $db->write('
            CREATE TABLE IF NOT EXISTS feature_drift_detection_results (
                check_id UUID,
                model_version String,
                feature_name String,
                vertical_code Nullable(String),
                metric_type String, -- PSI or KS
                drift_score Float64,
                threshold Float64,
                drift_level String, -- none, moderate, critical
                created_at DateTime64(3)
            )
            ENGINE = MergeTree()
            PARTITION BY toYYYYMM(created_at)
            ORDER BY (check_id, model_version, feature_name, vertical_code)
            TTL created_at + INTERVAL 30 DAY
        ');

        // Create table for drift alerts
        $db->write('
            CREATE TABLE IF NOT EXISTS feature_drift_alerts (
                alert_id UUID,
                model_version String,
                vertical_code Nullable(String),
                drifted_features_count UInt32,
                max_psi Float64,
                max_ks Float64,
                overall_drift_detected Bool,
                alert_sent Bool DEFAULT false,
                created_at DateTime64(3)
            )
            ENGINE = MergeTree()
            PARTITION BY toYYYYMM(created_at)
            ORDER BY (alert_id, model_version, vertical_code)
            TTL created_at + INTERVAL 60 DAY
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $db = \ClickHouseDB::getInstance();

        $db->write('DROP TABLE IF EXISTS feature_drift_alerts');
        $db->write('DROP TABLE IF EXISTS feature_drift_current_mv');
        $db->write('DROP TABLE IF EXISTS feature_drift_detection_results');
        $db->write('DROP TABLE IF EXISTS feature_drift_reference');
    }
};
