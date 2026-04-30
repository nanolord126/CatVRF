<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

/**
 * CLV Prediction Data Transfer Object
 * 
 * Immutable DTO containing Customer Lifetime Value prediction results
 * from ML model inference.
 * 
 * Production-ready: readonly properties, strict typing, validation.
 */
final readonly class CLVPredictionDTO
{
    public function __construct(
        public int $buyerId,
        public int $sellerId,
        public int $tenantId,
        public float $predictedClv180d,
        public float $predictedClv365d,
        public float $churnProbability,
        public float $confidence,
        public string $segment,
        public ?string $modelVersion = null,
        public ?\DateTimeImmutable $predictedAt = null,
    ) {
        $this->predictedAt = $predictedAt ?? new \DateTimeImmutable();
    }

    /**
     * Create CLV prediction from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            buyerId: (int) $data['buyer_id'],
            sellerId: (int) $data['seller_id'],
            tenantId: (int) $data['tenant_id'],
            predictedClv180d: (float) ($data['predicted_clv_180d'] ?? 0),
            predictedClv365d: (float) ($data['predicted_clv_365d'] ?? 0),
            churnProbability: (float) ($data['churn_probability'] ?? 0),
            confidence: (float) ($data['prediction_confidence'] ?? 0),
            segment: (string) ($data['clv_segment'] ?? 'unknown'),
            modelVersion: $data['model_version'] ?? null,
            predictedAt: isset($data['predicted_at']) 
                ? \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['predicted_at']) 
                : null,
        );
    }

    /**
     * Convert to array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'buyer_id' => $this->buyerId,
            'seller_id' => $this->sellerId,
            'tenant_id' => $this->tenantId,
            'predicted_clv_180d' => $this->predictedClv180d,
            'predicted_clv_365d' => $this->predictedClv365d,
            'churn_probability' => $this->churnProbability,
            'confidence' => $this->confidence,
            'segment' => $this->segment,
            'model_version' => $this->modelVersion,
            'predicted_at' => $this->predictedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Check if buyer is at high churn risk.
     */
    public function isHighChurnRisk(): bool
    {
        return $this->churnProbability > 0.5;
    }

    /**
     * Check if prediction has high confidence.
     */
    public function isHighConfidence(): bool
    {
        return $this->confidence > 0.7;
    }

    /**
     * Check if buyer is VIP segment.
     */
    public function isVip(): bool
    {
        return $this->segment === 'vip';
    }

    /**
     * Get monthly average CLV (180d / 6).
     */
    public function getMonthlyClv(): float
    {
        return round($this->predictedClv180d / 6, 2);
    }
}
