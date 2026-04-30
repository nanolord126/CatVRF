<?php

declare(strict_types=1);

namespace App\DTOs\Security;

/**
 * Adaptive Authentication Result DTO
 *
 * Contains the result of adaptive authentication risk evaluation.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class AdaptiveAuthResult
{
    public function __construct(
        public readonly float $overallRisk,
        public readonly string $riskLevel,
        public readonly array $stepUpRequired,
        public readonly array $behavioralData,
        public readonly string $correlationId,
        public readonly float $latencyMs,
        public readonly ?string $error = null,
    ) {}

    public function requiresStepUp(): bool
    {
        return ! empty($this->stepUpRequired);
    }

    public function requiresLiveness(): bool
    {
        return in_array('liveness', $this->stepUpRequired, true);
    }

    public function requiresManualReview(): bool
    {
        return in_array('manual_review', $this->stepUpRequired, true);
    }

    public function getStepUpMessage(): string
    {
        if (! $this->requiresStepUp()) {
            return 'Authentication successful';
        }

        return match (true) {
            $this->requiresManualReview() => 'Additional verification required. Please contact support.',
            $this->requiresLiveness() => 'Please complete liveness verification to continue.',
            in_array('behavioral', $this->stepUpRequired, true) => 'Please complete behavioral verification to continue.',
            default => 'Please complete additional verification to continue.',
        };
    }
}
