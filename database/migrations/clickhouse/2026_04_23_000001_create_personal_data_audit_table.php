<?php

declare(strict_types=1);

/**
 * ClickHouse Migration for Personal Data Audit
 * 
 * Creates the audit table for tracking all personal data access
 * in compliance with 152-FZ requirements.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */

return new class {
    public function up(): string
    {
        return <<<SQL
        CREATE TABLE IF NOT EXISTS personal_data_audit (
            event_type String,
            target_user_id UInt64,
            target_user_uuid UUID,
            accessor_user_id Nullable(UInt64),
            data_type Nullable(String),
            action Nullable(String),
            purpose Nullable(String),
            consent_type Nullable(String),
            is_biometric Nullable(UInt8),
            requires_enhanced_form Nullable(UInt8),
            signature_method Nullable(String),
            biometric_type Nullable(String),
            context Nullable(String),
            data_types Nullable(String),
            format Nullable(String),
            violation_type Nullable(String),
            reason Nullable(String),
            job_id Nullable(String),
            performed_by Nullable(String),
            requested_by Nullable(String),
            requires_jit_access Nullable(UInt8),
            ip_address Nullable(String),
            user_agent Nullable(String),
            timestamp DateTime64(3),
            correlation_id String,
            created_at DateTime64(3) DEFAULT now64(3)
        )
        ENGINE = MergeTree()
        PARTITION BY toYYYYMM(timestamp)
        ORDER BY (timestamp, target_user_id, event_type)
        TTL timestamp + INTERVAL 7 YEAR
        SETTINGS index_granularity = 8192;
        SQL;
    }

    public function down(): string
    {
        return 'DROP TABLE IF EXISTS personal_data_audit';
    }
};
