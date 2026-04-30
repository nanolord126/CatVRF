<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\ValueObjects;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\Enums\ThreatLevel;

/**
 * Threat Assessment Value Object
 *
 * Immutable assessment of a detected threat. Encapsulates threat level,
 * indicators, affected entities, and recommended actions.
 * Used by ThreatDetectionService and persisted via SecurityRepository.
 */
final readonly class ThreatAssessment
{
    /**
     * @param string $assessmentId Unique assessment identifier (UUID)
     * @param ThreatLevel $level Severity of the threat
     * @param string $threatType Category: volume_spike, query_anomaly, scraping, data_exfiltration, unauthorized_access, fraud_pattern
     * @param int|null $sellerId Affected seller (null if user-level)
     * @param int|null $userId Affected user (null if seller-level)
     * @param array $indicators Key-value pairs of anomaly indicators (e.g., ['event_count_1h' => 50000, 'sigma' => 4.2])
     * @param array $affectedTables ClickHouse tables involved
     * @param array $recommendedActions Suggested remediation steps
     * @param float $anomalyScore Normalized 0.0-1.0 score from detection model
     * @param bool $isAutoBlocked Whether the system auto-blocked the entity
     * @param CarbonImmutable $detectedAt When the threat was detected
     * @param CarbonImmutable|null $resolvedAt When the threat was resolved (null if active)
     * @param string $correlationId For distributed tracing across services
     */
    public function __construct(
        public readonly string $assessmentId,
        public readonly ThreatLevel $level,
        public readonly string $threatType,
        public readonly ?int $sellerId,
        public readonly ?int $userId,
        public readonly array $indicators,
        public readonly array $affectedTables,
        public readonly array $recommendedActions,
        public readonly float $anomalyScore,
        public readonly bool $isAutoBlocked,
        public readonly CarbonImmutable $detectedAt,
        public readonly ?CarbonImmutable $resolvedAt,
        public readonly string $correlationId,
    ) {}

    /**
     * Create from anomaly detection results (seller volume spike)
     */
    public static function fromSellerVolumeSpike(
        int $sellerId,
        int $eventCount1h,
        float $avgHourly,
        float $sigma,
        array $affectedTables,
        ?string $correlationId = null,
    ): self {
        $anomalyScore = min(1.0, $sigma / 5.0);
        $level = ThreatLevel::fromQueryDeviation($sigma);

        return new self(
            assessmentId: \Illuminate\Support\Str::uuid()->toString(),
            level: $level,
            threatType: 'volume_spike',
            sellerId: $sellerId,
            userId: null,
            indicators: [
                'event_count_1h' => $eventCount1h,
                'avg_hourly' => round($avgHourly, 2),
                'sigma' => round($sigma, 2),
                'ratio' => round($avgHourly > 0 ? $eventCount1h / $avgHourly : 0, 2),
            ],
            affectedTables: $affectedTables,
            recommendedActions: self::volumeSpikeActions($level),
            anomalyScore: $anomalyScore,
            isAutoBlocked: $level === ThreatLevel::Critical,
            detectedAt: CarbonImmutable::now(),
            resolvedAt: null,
            correlationId: $correlationId ?? \Illuminate\Support\Str::uuid()->toString(),
        );
    }

    /**
     * Create from query pattern anomaly (data mining / scraping detection)
     */
    public static function fromQueryAnomaly(
        int $userId,
        int $queryCount1h,
        float $avgDurationMs,
        int $distinctTables,
        int $maxRowsScanned,
        ?string $correlationId = null,
    ): self {
        // Scoring: high query count + many tables + large scans = scraping
        $score = min(1.0, (
            ($queryCount1h > 100 ? 0.3 : 0.0) +
            ($distinctTables > 5 ? 0.3 : 0.0) +
            ($maxRowsScanned > 1_000_000 ? 0.4 : 0.0)
        ));

        $level = ThreatLevel::fromAnomalyScore($score);

        return new self(
            assessmentId: \Illuminate\Support\Str::uuid()->toString(),
            level: $level,
            threatType: 'query_anomaly',
            sellerId: null,
            userId: $userId,
            indicators: [
                'query_count_1h' => $queryCount1h,
                'avg_duration_ms' => round($avgDurationMs, 2),
                'distinct_tables' => $distinctTables,
                'max_rows_scanned' => $maxRowsScanned,
                'anomaly_score' => round($score, 3),
            ],
            affectedTables: [],
            recommendedActions: self::queryAnomalyActions($level),
            anomalyScore: $score,
            isAutoBlocked: $level === ThreatLevel::Critical,
            detectedAt: CarbonImmutable::now(),
            resolvedAt: null,
            correlationId: $correlationId ?? \Illuminate\Support\Str::uuid()->toString(),
        );
    }

    /**
     * Create a resolved assessment (threat mitigated)
     */
    public function withResolved(): self
    {
        return new self(
            assessmentId: $this->assessmentId,
            level: $this->level,
            threatType: $this->threatType,
            sellerId: $this->sellerId,
            userId: $this->userId,
            indicators: $this->indicators,
            affectedTables: $this->affectedTables,
            recommendedActions: array_merge($this->recommendedActions, ['resolved']),
            anomalyScore: $this->anomalyScore,
            isAutoBlocked: false,
            detectedAt: $this->detectedAt,
            resolvedAt: CarbonImmutable::now(),
            correlationId: $this->correlationId,
        );
    }

    /**
     * Is this threat still active (not resolved)?
     */
    public function isActive(): bool
    {
        return $this->resolvedAt === null;
    }

    /**
     * Is this threat actionable (requires human or automated response)?
     */
    public function isActionable(): bool
    {
        return $this->level->isActionable() && $this->isActive();
    }

    /**
     * Should this threat trigger immediate notification?
     */
    public function shouldNotify(): bool
    {
        return $this->level->value >= ThreatLevel::High->value && $this->isActive();
    }

    /**
     * Should this threat be escalated to security team?
     */
    public function shouldEscalate(): bool
    {
        return $this->level === ThreatLevel::Critical && $this->isActive();
    }

    /**
     * Recommended actions for volume spike threats
     */
    private static function volumeSpikeActions(ThreatLevel $level): array
    {
        $actions = ['log_and_monitor'];

        if ($level->value >= ThreatLevel::Medium->value) {
            $actions[] = 'rate_limit_seller';
            $actions[] = 'notify_security_team';
        }

        if ($level->value >= ThreatLevel::High->value) {
            $actions[] = 'temporarily_suspend_producer';
            $actions[] = 'enable_waf_rule';
        }

        if ($level === ThreatLevel::Critical) {
            $actions[] = 'auto_block_seller';
            $actions[] = 'page_oncall';
            $actions[] = 'preserve_forensic_data';
        }

        return $actions;
    }

    /**
     * Recommended actions for query anomaly threats
     */
    private static function queryAnomalyActions(ThreatLevel $level): array
    {
        $actions = ['log_query_pattern'];

        if ($level->value >= ThreatLevel::Medium->value) {
            $actions[] = 'restrict_query_access';
            $actions[] = 'require_mfa_for_queries';
        }

        if ($level->value >= ThreatLevel::High->value) {
            $actions[] = 'revoke_ad_hoc_access';
            $actions[] = 'notify_dpo';
        }

        if ($level === ThreatLevel::Critical) {
            $actions[] = 'suspend_user_access';
            $actions[] = 'forensic_audit_trail';
            $actions[] = 'page_oncall';
        }

        return $actions;
    }

    public function toArray(): array
    {
        return [
            'assessment_id' => $this->assessmentId,
            'level' => $this->level->value,
            'threat_type' => $this->threatType,
            'seller_id' => $this->sellerId,
            'user_id' => $this->userId,
            'indicators' => $this->indicators,
            'affected_tables' => $this->affectedTables,
            'recommended_actions' => $this->recommendedActions,
            'anomaly_score' => round($this->anomalyScore, 3),
            'is_auto_blocked' => $this->isAutoBlocked,
            'detected_at' => $this->detectedAt->toIso8601String(),
            'resolved_at' => $this->resolvedAt?->toIso8601String(),
            'correlation_id' => $this->correlationId,
            'is_active' => $this->isActive(),
            'is_actionable' => $this->isActionable(),
        ];
    }
}
