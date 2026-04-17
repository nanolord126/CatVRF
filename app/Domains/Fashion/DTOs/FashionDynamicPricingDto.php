<?php declare(strict_types=1);

namespace App\Domains\Fashion\DTOs;

use Carbon\Carbon;

final readonly class FashionDynamicPricingDto
{
    public function __construct(
        public int $productId,
        public int $tenantId,
        public ?int $businessGroupId,
        public float $basePrice,
        public float $dynamicPrice,
        public float $discountPercent,
        public float $trendScore,
        public bool $isFlashSale,
        public ?Carbon $flashSaleEndTime,
        public string $correlationId,
    ) {}

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'base_price' => $this->basePrice,
            'dynamic_price' => $this->dynamicPrice,
            'discount_percent' => $this->discountPercent,
            'trend_score' => $this->trendScore,
            'is_flash_sale' => $this->isFlashSale,
            'flash_sale_end_time' => $this->flashSaleEndTime?->toIso8601String(),
            'correlation_id' => $this->correlationId,
        ];
    }
}
