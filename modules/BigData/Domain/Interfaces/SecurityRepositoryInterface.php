<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Interfaces;

use Modules\BigData\Domain\Enums\DataCategory;
use Modules\BigData\Domain\Enums\ThreatLevel;
use Modules\BigData\Domain\ValueObjects\SecurityContext;
use Modules\BigData\Domain\ValueObjects\ThreatAssessment;

/**
 * Security Repository Interface
 *
 * Contract for reading/writing security-related data from infrastructure
 * (ClickHouse audit_log, Kafka security_audit topic, Redis rate limits).
 */
interface SecurityRepositoryInterface
{
    /**
     * Write an immutable audit log entry to ClickHouse
     * Append-only: no UPDATE/DELETE allowed
     */
    public function appendAuditLog(array $entry): bool;

    /**
     * Get audit logs for a resource
     * @return array<array<string, mixed>>
     */
    public function getAuditLogs(string $resource, ?int $userId = null, int $limit = 100): array;

    /**
     * Check row-level access for a seller
     * Uses ClickHouse current_user_setting('seller_id')
     */
    public function checkRowLevelAccess(int $sellerId, string $table): bool;

    /**
     * Get query pattern metrics for anomaly detection
     * @return array{query_count_1h: int, avg_query_duration_ms: float, distinct_tables: int, max_rows_scanned: int}
     */
    public function getQueryPatternMetrics(int $userId): array;

    /**
     * Get event volume for a seller (for anomaly detection)
     * @return array{event_count_1h: int, event_count_24h: int, avg_hourly: float}
     */
    public function getSellerEventVolume(int $sellerId): array;

    /**
     * Record a threat detection event
     */
    public function recordThreatEvent(ThreatAssessment $assessment): bool;

    /**
     * Get active threats for a seller or user
     * @return array<ThreatAssessment>
     */
    public function getActiveThreats(?int $sellerId = null, ?int $userId = null, int $limit = 50): array;

    /**
     * Execute GDPR deletion for a user across all BigData tables
     * Returns counts per table
     * @return array<string, int> table_name => rows_deleted
     */
    public function executeGDPRDeletion(int $userId): array;

    /**
     * Get PII inventory for a user across all tables
     * @return array<string, array{table: string, fields: array<string>, row_count: int}>
     */
    public function getPIIInventory(int $userId): array;

    /**
     * Get compliance metrics snapshot
     * @return array{encryption_coverage: float, retention_compliance: float, audit_coverage: float, pii_inventory_count: int, active_threats: int}
     */
    public function getComplianceMetrics(): array;

    /**
     * Publish security event to Kafka security_audit topic (WORM)
     */
    public function publishSecurityAuditEvent(array $event): bool;
}
