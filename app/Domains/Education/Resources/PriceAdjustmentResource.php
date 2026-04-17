<?php declare(strict_types=1);

namespace App\Domains\Education\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Education\DTOs\PriceAdjustmentDto;

final class PriceAdjustmentResource extends JsonResource
{
    public function toArray($request): array
    {
        $adjustment = $this->resource instanceof PriceAdjustmentDto 
            ? $this->resource 
            : PriceAdjustmentDto::fromArray($this->resource);

        return [
            'price_id' => $adjustment->priceId,
            'original_price_rub' => $adjustment->originalPriceKopecks / 100,
            'adjusted_price_rub' => $adjustment->adjustedPriceKopecks / 100,
            'discount_percent' => $adjustment->discountPercent,
            'adjustment_reason' => $adjustment->adjustmentReason,
            'factors' => $adjustment->factors,
            'valid_until' => $adjustment->validUntil,
            'is_flash_sale' => $adjustment->isFlashSale,
            'generated_at' => $adjustment->generatedAt,
        ];
    }
}
