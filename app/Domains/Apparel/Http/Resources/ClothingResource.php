<?php

declare(strict_types=1);

namespace App\Domains\Apparel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ClothingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'brand' => $this->brand,
            'size' => $this->size,
            'color' => $this->color,
            'material' => $this->material,
            'price' => (float) $this->price,
            'stock_quantity' => (int) $this->stock_quantity,
            'sku' => $this->sku,
            'images' => $this->images ? json_decode($this->images, true) : null,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
