<?php declare(strict_types=1);

namespace App\Domains\Education\DTOs;

final readonly class PriceAdjustmentDto
{
    public function __construct(
        public string $priceId,
        public int $originalPriceKopecks,
        public int $adjustedPriceKopecks,
        public float $discountPercent,
        public string $adjustmentReason,
        public array $factors,
        public string $validUntil,
        public bool $isFlashSale,
        public string $generatedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'price_id' => $this->priceId,
            'original_price_kopecks' => $this->originalPriceKopecks,
            'adjusted_price_kopecks' => $this->adjustedPriceKopecks,
            'discount_percent' => $this->discountPercent,
            'adjustment_reason' => $this->adjustmentReason,
            'factors' => $this->factors,
            'valid_until' => $this->validUntil,
            'is_flash_sale' => $this->isFlashSale,
            'generated_at' => $this->generatedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            priceId: $data['price_id'],
            originalPriceKopecks: $data['original_price_kopecks'],
            adjustedPriceKopecks: $data['adjusted_price_kopecks'],
            discountPercent: $data['discount_percent'],
            adjustmentReason: $data['adjustment_reason'],
            factors: $data['factors'],
            validUntil: $data['valid_until'],
            isFlashSale: $data['is_flash_sale'],
            generatedAt: $data['generated_at'],
        );
    }
}
