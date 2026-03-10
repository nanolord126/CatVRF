<?php

declare(strict_types=1);

namespace App\Domains\RealEstateRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RentalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'address' => $this->address,
            'rent_price' => (float) $this->rent_price,
            'deposit' => (float) $this->deposit,
            'lease_term_months' => $this->lease_term_months,
            'available_from' => $this->available_from?->format('Y-m-d'),
            'area_sqm' => $this->area_sqm,
            'area_hectares' => $this->area_hectares,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
