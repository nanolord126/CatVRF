<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Entities;

final class SellerRecommendation
{
    public function __construct(
        private readonly int $sellerId,
        private readonly int $tenantId,
        private readonly int $productId,
        private readonly float $predictedUplift,
        private readonly float $confidence,
        private readonly string $reason,
        private readonly array $supportingData = [],
    ) {}

    public function getSellerId(): int { return $this->sellerId; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getPredictedUplift(): float { return $this->predictedUplift; }
    public function getConfidence(): float { return $this->confidence; }
    public function getReason(): string { return $this->reason; }
    public function getSupportingData(): array { return $this->supportingData; }

    public function toArray(): array
    {
        return [
            'seller_id' => $this->sellerId,
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'predicted_uplift' => round($this->predictedUplift, 4),
            'confidence' => round($this->confidence, 4),
            'reason' => $this->reason,
            'supporting_data' => $this->supportingData,
        ];
    }
}
