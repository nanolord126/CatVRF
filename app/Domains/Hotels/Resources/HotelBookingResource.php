<?php declare(strict_types=1);

namespace App\Domains\Hotels\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class HotelBookingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->resource->id,
            'uuid'             => $this->resource->uuid,
            'tenant_id'        => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'hotel_id'         => $this->resource->hotel_id,
            'room_id'          => $this->resource->room_id,
            'guest_name'       => $this->resource->guest_name,
            'guest_phone'      => $this->resource->guest_phone,
            'guest_email'      => $this->resource->guest_email,
            'check_in'         => $this->resource->check_in,
            'check_out'        => $this->resource->check_out,
            'guests_count'     => $this->resource->guests_count,
            'total_amount'     => $this->resource->total_amount,
            'status'           => $this->resource->status,
            'services'         => $this->resource->services,
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
