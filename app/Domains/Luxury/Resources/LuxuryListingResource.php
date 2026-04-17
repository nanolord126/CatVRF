<?php declare(strict_types=1);

namespace App\Domains\Luxury\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LuxuryListingResource extends JsonResource
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
            'category'         => $this->resource->category,
            'name'             => $this->resource->name,
            'price'            => $this->resource->price,
            'authenticity'     => $this->resource->authenticity,
            'certificate_url'  => $this->resource->certificate_url,
            'condition'        => $this->resource->condition,
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
