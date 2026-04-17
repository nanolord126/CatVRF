<?php declare(strict_types=1);

namespace App\Domains\Fashion\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FashionProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->resource->id,
            'uuid'             => $this->resource->uuid,
            'tenant_id'        => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'brand_id'         => $this->resource->brand_id,
            'name'             => $this->resource->name,
            'gender'           => $this->resource->gender,
            'season'           => $this->resource->season,
            'sizes'            => $this->resource->sizes,
            'colors'           => $this->resource->colors,
            'material'         => $this->resource->material,
            'price'            => $this->resource->price,
            'stock'            => $this->resource->stock,
            'is_active'        => $this->resource->is_active,
            'tags'             => $this->resource->tags,
            'correlation_id'   => $this->resource->correlation_id,
            'created_at'       => $this->resource->created_at,
            'updated_at'       => $this->resource->updated_at,
        ];
    }

    /** @return array<string, mixed> */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->attributes->get('correlation_id'),
                'generated_at'   => now()->toIso8601String(),
            ],
        ];
    }
}
