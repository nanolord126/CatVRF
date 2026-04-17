<?php declare(strict_types=1);

namespace App\Domains\GeoLogistics\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LogisticsRouteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->resource->id,
            'uuid'                => $this->resource->uuid,
            'tenant_id'           => $this->resource->tenant_id,
            'business_group_id'   => $this->resource->business_group_id,
            'origin_address'      => $this->resource->origin_address,
            'origin_lat'          => $this->resource->origin_lat,
            'origin_lon'          => $this->resource->origin_lon,
            'destination_address' => $this->resource->destination_address,
            'destination_lat'     => $this->resource->destination_lat,
            'destination_lon'     => $this->resource->destination_lon,
            'transport_type'      => $this->resource->transport_type,
            'distance_km'         => $this->resource->distance_km,
            'price'               => $this->resource->price,
            'status'              => $this->resource->status,
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
