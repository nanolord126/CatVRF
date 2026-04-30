<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Events;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\Enums\DataCategory;
use Modules\BigData\Domain\Enums\ThreatLevel;
use Modules\BigData\Domain\ValueObjects\ThreatAssessment;

/**
 * Security Threat Detected Domain Event
 *
 * Dispatched when anomaly detection identifies a potential security threat.
 * Carries full threat context for listeners (audit, notifications, auto-blocking).
 */
final class SecurityThreatDetected
{
    public readonly CarbonImmutable $occurredAt;

    public function __construct(
        public readonly string $assessmentId,
        public readonly ThreatLevel $level,
        public readonly string $threatType,
        public readonly ?int $sellerId,
        public readonly ?int $userId,
        public readonly float $anomalyScore,
        public readonly array $indicators,
        public readonly array $recommendedActions,
        public readonly bool $isAutoBlocked,
        public readonly ?string $correlationId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? CarbonImmutable::now();
    }

    /**
     * Create from ThreatAssessment value object
     */
    public static function fromAssessment(ThreatAssessment $assessment): self
    {
        return new self(
            assessmentId: $assessment->assessmentId,
            level: $assessment->level,
            threatType: $assessment->threatType,
            sellerId: $assessment->sellerId,
            userId: $assessment->userId,
            anomalyScore: $assessment->anomalyScore,
            indicators: $assessment->indicators,
            recommendedActions: $assessment->recommendedActions,
            isAutoBlocked: $assessment->isAutoBlocked,
            correlationId: $assessment->correlationId,
        );
    }

    /**
     * Should this trigger PagerDuty?
     */
    public function shouldPage(): bool
    {
        return $this->level === ThreatLevel::Critical;
    }

    /**
     * Should this trigger Slack/Telegram notification?
     */
    public function shouldNotify(): bool
    {
        return $this->level->value >= ThreatLevel::Medium->value;
    }

    /**
     * Generate notification text
     */
    public function toNotificationText(): string
    {
        $emoji = $this->level === ThreatLevel::Critical ? '🚨' : '⚠️';
        $severity = strtoupper($this->level->name);
        $entity = $this->sellerId ? "seller#{$this->sellerId}" : "user#{$this->userId}";
        $score = round($this->anomalyScore * 100, 1);

        return "{$emoji} [BIGDATA-SEC][{$severity}] {$this->threatType} detected for {$entity} (score: {$score}%)";
    }

    public function toArray(): array
    {
        return [
            'event' => 'security_threat_detected',
            'assessment_id' => $this->assessmentId,
            'level' => $this->level->value,
            'threat_type' => $this->threatType,
            'seller_id' => $this->sellerId,
            'user_id' => $this->userId,
            'anomaly_score' => round($this->anomalyScore, 3),
            'indicators' => $this->indicators,
            'recommended_actions' => $this->recommendedActions,
            'is_auto_blocked' => $this->isAutoBlocked,
            'correlation_id' => $this->correlationId,
            'occurred_at' => $this->occurredAt->toIso8601String(),
            'should_page' => $this->shouldPage(),
            'should_notify' => $this->shouldNotify(),
        ];
    }
}
