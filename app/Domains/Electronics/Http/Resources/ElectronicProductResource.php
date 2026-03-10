<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ElectronicProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand,
            'category' => $this->category,
            'price' => (float) $this->price,
            'stock' => (int) $this->stock,
            'description' => $this->description,
            'specifications' => $this->specifications ? json_decode($this->specifications, true) : null,
            'warranty_months' => $this->warranty_months,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
