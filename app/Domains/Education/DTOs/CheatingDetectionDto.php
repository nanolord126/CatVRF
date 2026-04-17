<?php declare(strict_types=1);

namespace App\Domains\Education\DTOs;

final readonly class CheatingDetectionDto
{
    public function __construct(
        public string $detectionId,
        public int $userId,
        public int $enrollmentId,
        public int $tenantId,
        public ?int $businessGroupId,
        public bool $isCheating,
        public float $cheatingProbability,
        public array $riskFactors,
        public string $severity,
        public string $correlationId,
        public string $detectedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'detection_id' => $this->detectionId,
            'user_id' => $this->userId,
            'enrollment_id' => $this->enrollmentId,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'is_cheating' => $this->isCheating,
            'cheating_probability' => $this->cheatingProbability,
            'risk_factors' => $this->riskFactors,
            'severity' => $this->severity,
            'correlation_id' => $this->correlationId,
            'detected_at' => $this->detectedAt,
        ];
    }
}
