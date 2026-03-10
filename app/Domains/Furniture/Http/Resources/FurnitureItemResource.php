<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FurnitureItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'material' => $this->material,
            'color' => $this->color,
            'dimensions' => $this->dimensions,
            'price' => (float) $this->price,
            'stock' => (int) $this->stock,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
