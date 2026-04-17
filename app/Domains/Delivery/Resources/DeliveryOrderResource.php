<?php declare(strict_types=1);

namespace App\Domains\Delivery\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DeliveryOrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->resource->id,
            'uuid'             => $this->resource->uuid,
            'tenant_id'        => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'courier_id'       => $this->resource->courier_id,
            'status'           => $this->resource->status,
            'package_type'     => $this->resource->package_type,
            'weight_kg'        => $this->resource->weight_kg,
            'recipient_name'   => $this->resource->recipient_name,
            'recipient_phone'  => $this->resource->recipient_phone,
            'pickup_point'     => $this->resource->pickup_point,
            'dropoff_point'    => $this->resource->dropoff_point,
            'delivery_fee'     => $this->resource->delivery_fee,
            'estimated_at'     => $this->resource->estimated_at,
            'delivered_at'     => $this->resource->delivered_at,
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
