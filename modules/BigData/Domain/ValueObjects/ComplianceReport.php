<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\ValueObjects;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\Enums\ComplianceStatus;
use Modules\BigData\Domain\Enums\DataCategory;

/**
 * Compliance Report Value Object
 *
 * Immutable snapshot of compliance posture across all BigData tables.
 * Covers GDPR/152-FZ retention, encryption coverage, PII inventory,
 * and audit trail completeness. Generated monthly by ComplianceReportJob.
 */
final readonly class ComplianceReport
{
    /**
     * @param string $reportId Unique report identifier
     * @param CarbonImmutable $generatedAt When the report was generated
     * @param string $period Period covered (e.g., '2026-04')
     * @param ComplianceStatus $overallStatus Aggregate compliance status
     * @param float $encryptionCoverage 0.0-1.0 fraction of sensitive fields encrypted
     * @param float $retentionCompliance 0.0-1.0 fraction of tables within retention policy
     * @param float $auditCoverage 0.0-1.0 fraction of data accesses with audit log
     * @param int $piiInventoryCount Number of PII fields tracked across all tables
     * @param int $activeThreats Number of unresolved threat assessments
     * @param int $gdprDeletionRequestsPending Pending Right-to-be-Forgotten requests
     * @param int $gdprDeletionRequestsCompleted Completed deletions this period
     * @param array $tableCompliance Per-table compliance breakdown
     * @param array $violations List of specific compliance violations
     * @param array $recommendations Recommended remediation actions
     * @param array $metrics Raw metrics for audit evidence
     */
    public function __construct(
        public readonly string $reportId,
        public readonly CarbonImmutable $generatedAt,
        public readonly string $period,
        public readonly ComplianceStatus $overallStatus,
        public readonly float $encryptionCoverage,
        public readonly float $retentionCompliance,
        public readonly float $auditCoverage,
        public readonly int $piiInventoryCount,
        public readonly int $activeThreats,
        public readonly int $gdprDeletionRequestsPending,
        public readonly int $gdprDeletionRequestsCompleted,
        public readonly array $tableCompliance,
        public readonly array $violations,
        public readonly array $recommendations,
        public readonly array $metrics,
    ) {}

    /**
     * Generate a compliance report from raw infrastructure metrics
     */
    public static function fromMetrics(array $metrics, string $period): self
    {
        $encryptionCoverage = $metrics['encryption_coverage'] ?? 0.0;
        $retentionCompliance = $metrics['retention_compliance'] ?? 0.0;
        $auditCoverage = $metrics['audit_coverage'] ?? 0.0;

        $overallStatus = self::determineOverallStatus(
            $encryptionCoverage,
            $retentionCompliance,
            $auditCoverage,
        );

        $violations = self::detectViolations($metrics);
        $recommendations = self::generateRecommendations($violations, $metrics);

        return new self(
            reportId: \Illuminate\Support\Str::uuid()->toString(),
            generatedAt: CarbonImmutable::now(),
            period: $period,
            overallStatus: $overallStatus,
            encryptionCoverage: $encryptionCoverage,
            retentionCompliance: $retentionCompliance,
            auditCoverage: $auditCoverage,
            piiInventoryCount: $metrics['pii_inventory_count'] ?? 0,
            activeThreats: $metrics['active_threats'] ?? 0,
            gdprDeletionRequestsPending: $metrics['gdpr_pending'] ?? 0,
            gdprDeletionRequestsCompleted: $metrics['gdpr_completed'] ?? 0,
            tableCompliance: self::buildTableCompliance($metrics),
            violations: $violations,
            recommendations: $recommendations,
            metrics: $metrics,
        );
    }

    /**
     * Determine overall compliance status from key metrics
     */
    private static function determineOverallStatus(
        float $encryption,
        float $retention,
        float $audit,
    ): ComplianceStatus {
        // Any metric below 80% is a violation
        if ($encryption < 0.8 || $retention < 0.8 || $audit < 0.8) {
            return ComplianceStatus::Violation;
        }

        // Any metric below 95% is a warning
        if ($encryption < 0.95 || $retention < 0.95 || $audit < 0.95) {
            return ComplianceStatus::Warning;
        }

        return ComplianceStatus::Compliant;
    }

    /**
     * Detect specific compliance violations from raw metrics
     */
    private static function detectViolations(array $metrics): array
    {
        $violations = [];

        // GDPR Article 32: encryption of personal data
        if (($metrics['encryption_coverage'] ?? 0) < 1.0) {
            $violations[] = [
                'regulation' => 'GDPR Art.32',
                'article' => 'Encryption of personal data',
                'severity' => ($metrics['encryption_coverage'] ?? 0) < 0.8 ? 'critical' : 'warning',
                'current' => round(($metrics['encryption_coverage'] ?? 0) * 100, 1) . '%',
                'required' => '100%',
                'description' => 'Not all PII fields are encrypted at rest',
            ];
        }

        // 152-FZ: data retention compliance
        if (($metrics['retention_compliance'] ?? 0) < 1.0) {
            $violations[] = [
                'regulation' => '152-FZ Art.21',
                'article' => 'Data retention limits',
                'severity' => ($metrics['retention_compliance'] ?? 0) < 0.9 ? 'critical' : 'warning',
                'current' => round(($metrics['retention_compliance'] ?? 0) * 100, 1) . '%',
                'required' => '100%',
                'description' => 'Some data categories exceed retention limits',
            ];
        }

        // PCI-DSS Requirement 10: audit trail
        if (($metrics['audit_coverage'] ?? 0) < 1.0) {
            $violations[] = [
                'regulation' => 'PCI-DSS Req.10',
                'article' => 'Track and monitor all access',
                'severity' => ($metrics['audit_coverage'] ?? 0) < 0.9 ? 'critical' : 'warning',
                'current' => round(($metrics['audit_coverage'] ?? 0) * 100, 1) . '%',
                'required' => '100%',
                'description' => 'Not all data accesses are audit-logged',
            ];
        }

        // GDPR Art.17: Right to be Forgotten response time
        if (($metrics['gdpr_pending'] ?? 0) > 0) {
            $violations[] = [
                'regulation' => 'GDPR Art.17',
                'article' => 'Right to erasure (Right to be Forgotten)',
                'severity' => ($metrics['gdpr_pending'] ?? 0) > 10 ? 'critical' : 'warning',
                'current' => ($metrics['gdpr_pending'] ?? 0) . ' pending',
                'required' => '0 pending (30-day SLA)',
                'description' => 'Outstanding GDPR deletion requests',
            ];
        }

        // Active threats without resolution
        if (($metrics['active_threats'] ?? 0) > 5) {
            $violations[] = [
                'regulation' => 'Internal Security Policy',
                'article' => 'Threat response SLA',
                'severity' => 'warning',
                'current' => ($metrics['active_threats'] ?? 0) . ' active threats',
                'required' => '< 5 active threats',
                'description' => 'Too many unresolved security threats',
            ];
        }

        return $violations;
    }

    /**
     * Generate remediation recommendations based on violations
     */
    private static function generateRecommendations(array $violations, array $metrics): array
    {
        $recommendations = [];

        foreach ($violations as $violation) {
            $recommendations[] = match ($violation['regulation']) {
                'GDPR Art.32' => 'Enable AES-256-GCM field-level encryption for all PII columns in ClickHouse. Run: BigData::security()->encryptSensitive($payload)',
                '152-FZ Art.21' => 'Apply TTL to all ClickHouse tables per DataCategory::retentionDays(). Run: BigData::security()->enforceRetention()',
                'PCI-DSS Req.10' => 'Ensure all ClickHouse queries go through audited ClickHouseClient. Enable query_log in CH config.',
                'GDPR Art.17' => 'Process pending GDPR deletion requests. Run: BigData::security()->verifyGDPRRequest($userId)',
                'Internal Security Policy' => 'Review and resolve active threat assessments. Run: BigData::security()->getActiveThreats()',
                default => "Review and remediate: {$violation['description']}",
            };
        }

        // Add proactive recommendations
        if (($metrics['encryption_coverage'] ?? 0) >= 0.9 && ($metrics['encryption_coverage'] ?? 0) < 1.0) {
            $recommendations[] = 'Rotate encryption keys for TopSecret data (quarterly rotation due).';
        }

        if (empty($violations)) {
            $recommendations[] = 'No violations detected. Schedule next penetration test within 90 days.';
        }

        return $recommendations;
    }

    /**
     * Build per-table compliance breakdown from metrics
     */
    private static function buildTableCompliance(array $metrics): array
    {
        $result = [];

        foreach (DataCategory::cases() as $category) {
            $tableName = $category->toTableName();
            $result[$tableName] = [
                'category' => $category->value,
                'classification' => \Modules\BigData\Domain\Enums\DataClassification::fromDataCategory($category)->value,
                'retention_days_required' => $category->retentionDays(),
                'pii_fields' => $category->piiFields(),
                'supports_gdpr_deletion' => $category->supportsGDPRDeletion(),
            ];
        }

        return $result;
    }

    /**
     * Is the system fully compliant?
     */
    public function isCompliant(): bool
    {
        return $this->overallStatus->isCompliant();
    }

    /**
     * Summary for executive reporting
     */
    public function executiveSummary(): array
    {
        return [
            'period' => $this->period,
            'status' => $this->overallStatus->value,
            'encryption_coverage' => round($this->encryptionCoverage * 100, 1) . '%',
            'retention_compliance' => round($this->retentionCompliance * 100, 1) . '%',
            'audit_coverage' => round($this->auditCoverage * 100, 1) . '%',
            'active_threats' => $this->activeThreats,
            'gdpr_pending' => $this->gdprDeletionRequestsPending,
            'violations_count' => count($this->violations),
            'critical_violations' => count(array_filter($this->violations, fn($v) => ($v['severity'] ?? '') === 'critical')),
        ];
    }

    public function toArray(): array
    {
        return [
            'report_id' => $this->reportId,
            'generated_at' => $this->generatedAt->toIso8601String(),
            'period' => $this->period,
            'overall_status' => $this->overallStatus->value,
            'encryption_coverage' => round($this->encryptionCoverage, 3),
            'retention_compliance' => round($this->retentionCompliance, 3),
            'audit_coverage' => round($this->auditCoverage, 3),
            'pii_inventory_count' => $this->piiInventoryCount,
            'active_threats' => $this->activeThreats,
            'gdpr_deletion_pending' => $this->gdprDeletionRequestsPending,
            'gdpr_deletion_completed' => $this->gdprDeletionRequestsCompleted,
            'table_compliance' => $this->tableCompliance,
            'violations' => $this->violations,
            'recommendations' => $this->recommendations,
        ];
    }
}
