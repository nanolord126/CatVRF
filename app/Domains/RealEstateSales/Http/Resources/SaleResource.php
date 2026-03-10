<?php

declare(strict_types=1);

namespace App\Domains\RealEstateSales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'address' => $this->address,
            'price' => (float) $this->price,
            'area_sqm' => $this->area_sqm,
            'area_hectares' => $this->area_hectares,
            'year_built' => $this->year_built,
            'condition' => $this->condition,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
