<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ApartmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->resource->id,
            'uuid'             => $this->resource->uuid,
            'tenant_id'        => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'owner_id'         => $this->resource->owner_id,
            'name'             => $this->resource->name,
            'address'          => $this->resource->address,
            'lat'              => $this->resource->lat,
            'lon'              => $this->resource->lon,
            'rooms'            => $this->resource->rooms,
            'area_sqm'         => $this->resource->area_sqm,
            'floor'            => $this->resource->floor,
            'price_per_night'  => $this->resource->price_per_night,
            'deposit_amount'   => $this->resource->deposit_amount,
            'amenities'        => $this->resource->amenities,
            'images'           => $this->resource->images,
            'check_in_time'    => $this->resource->check_in_time,
            'check_out_time'   => $this->resource->check_out_time,
            'min_nights'       => $this->resource->min_nights,
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
