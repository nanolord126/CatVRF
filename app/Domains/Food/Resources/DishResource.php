<?php declare(strict_types=1);

namespace App\Domains\Food\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DishResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->resource->id,
            'uuid'            => $this->resource->uuid,
            'tenant_id'       => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'restaurant_id'   => $this->resource->restaurant_id,
            'name'            => $this->resource->name,
            'price'           => $this->resource->price,
            'weight_grams'    => $this->resource->weight_grams,
            'calories'        => $this->resource->calories,
            'proteins'        => $this->resource->proteins,
            'fats'            => $this->resource->fats,
            'carbohydrates'   => $this->resource->carbohydrates,
            'allergens'       => $this->resource->allergens,
            'modifiers'       => $this->resource->modifiers,
            'is_available'    => $this->resource->is_available,
            'tags'            => $this->resource->tags,
            'correlation_id'  => $this->resource->correlation_id,
            'created_at'      => $this->resource->created_at,
            'updated_at'      => $this->resource->updated_at,
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
