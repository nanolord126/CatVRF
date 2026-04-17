<?php declare(strict_types=1);

namespace App\Domains\Travel\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TravelOrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->resource->id,
            'uuid'                       => $this->resource->uuid,
            'tenant_id'                  => $this->resource->tenant_id,
            'business_group_id'          => $this->resource->business_group_id,
            'b2b_travel_storefront_id'   => $this->resource->b2b_travel_storefront_id,
            'order_number'               => $this->resource->order_number,
            'company_contact_person'     => $this->resource->company_contact_person,
            'company_phone'              => $this->resource->company_phone,
            'items'                      => $this->resource->items,
            'total_amount'               => $this->resource->total_amount,
            'commission_amount'          => $this->resource->commission_amount,
            'departure_date'             => $this->resource->departure_date,
            'return_date'                => $this->resource->return_date,
            'destination'                => $this->resource->destination,
            'travellers_count'           => $this->resource->travellers_count,
            'status'                     => $this->resource->status,
            'rejection_reason'           => $this->resource->rejection_reason,
            'tags'                       => $this->resource->tags,
            'correlation_id'             => $this->resource->correlation_id,
            'created_at'                 => $this->resource->created_at,
            'updated_at'                 => $this->resource->updated_at,
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
