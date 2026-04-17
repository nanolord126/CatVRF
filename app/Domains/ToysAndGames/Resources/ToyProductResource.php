<?php declare(strict_types=1);

namespace App\Domains\ToysAndGames\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ToyProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->resource->id,
            'uuid'                => $this->resource->uuid,
            'tenant_id'           => $this->resource->tenant_id,
            'business_group_id'   => $this->resource->business_group_id,
            'name'                => $this->resource->name,
            'category'            => $this->resource->category,
            'brand'               => $this->resource->brand,
            'age_min_years'       => $this->resource->age_min_years,
            'age_max_years'       => $this->resource->age_max_years,
            'price'               => $this->resource->price,
            'stock'               => $this->resource->stock,
            'materials'           => $this->resource->materials,
            'safety_certificates' => $this->resource->safety_certificates,
            'is_educational'      => $this->resource->is_educational,
            'is_active'           => $this->resource->is_active,
            'tags'                => $this->resource->tags,
            'correlation_id'      => $this->resource->correlation_id,
            'created_at'          => $this->resource->created_at,
            'updated_at'          => $this->resource->updated_at,
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
