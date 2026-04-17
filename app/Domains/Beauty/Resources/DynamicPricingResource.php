<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DynamicPricingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'base_price' => $this['base_price'],
            'demand_score' => $this['demand_score'],
            'surge_multiplier' => $this['surge_multiplier'],
            'flash_discount_percent' => $this['flash_discount_percent'],
            'final_price' => $this['final_price'],
            'is_surge_pricing' => $this['is_surge_pricing'],
            'is_flash_discount' => $this['is_flash_discount'],
            'price_change_percent' => $this['base_price'] > 0
                ? round((($this['final_price'] - $this['base_price']) / $this['base_price']) * 100, 2)
                : 0,
        ];
    }
}
