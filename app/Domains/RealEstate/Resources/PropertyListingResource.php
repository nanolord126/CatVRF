<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PropertyListingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->resource->id,
            'uuid'             => $this->resource->uuid,
            'tenant_id'        => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'type'             => $this->resource->type,
            'deal_type'        => $this->resource->deal_type,
            'address'          => $this->resource->address,
            'lat'              => $this->resource->lat,
            'lon'              => $this->resource->lon,
            'area_sqm'         => $this->resource->area_sqm,
            'rooms'            => $this->resource->rooms,
            'floor'            => $this->resource->floor,
            'floors_total'     => $this->resource->floors_total,
            'price'            => $this->resource->price,
            'features'         => $this->resource->features,
            'images'           => $this->resource->images,
            'status'           => $this->resource->status,
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
