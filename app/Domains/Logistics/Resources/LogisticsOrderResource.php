<?php declare(strict_types=1);

namespace App\Domains\Logistics\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LogisticsOrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->resource->id,
            'uuid'                  => $this->resource->uuid,
            'tenant_id'             => $this->resource->tenant_id,
            'business_group_id'     => $this->resource->business_group_id,
            'origin_warehouse_id'   => $this->resource->origin_warehouse_id,
            'destination_address'   => $this->resource->destination_address,
            'destination_lat'       => $this->resource->destination_lat,
            'destination_lon'       => $this->resource->destination_lon,
            'cargo_type'            => $this->resource->cargo_type,
            'transport_type'        => $this->resource->transport_type,
            'weight_kg'             => $this->resource->weight_kg,
            'volume_m3'             => $this->resource->volume_m3,
            'price'                 => $this->resource->price,
            'status'                => $this->resource->status,
            'estimated_delivery_at' => $this->resource->estimated_delivery_at,
            'tags'                  => $this->resource->tags,
            'correlation_id'        => $this->resource->correlation_id,
            'created_at'            => $this->resource->created_at,
            'updated_at'            => $this->resource->updated_at,
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
