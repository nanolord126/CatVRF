<?php declare(strict_types=1);

namespace App\Domains\Education\DTOs;

final readonly class ReviewFraudDto
{
    public function __construct(
        public string $detectionId,
        public int $reviewId,
        public int $userId,
        public int $tenantId,
        public bool $isFraudulent,
        public float $fraudProbability,
        public array $fraudIndicators,
        public string $severity,
        public string $correlationId,
        public string $detectedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'detection_id' => $this->detectionId,
            'review_id' => $this->reviewId,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'is_fraudulent' => $this->isFraudulent,
            'fraud_probability' => $this->fraudProbability,
            'fraud_indicators' => $this->fraudIndicators,
            'severity' => $this->severity,
            'correlation_id' => $this->correlationId,
            'detected_at' => $this->detectedAt,
        ];
    }
}
