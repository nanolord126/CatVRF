<?php declare(strict_types=1);

namespace App\Domains\Tickets\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TicketResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->resource->id,
            'uuid'              => $this->resource->uuid,
            'tenant_id'         => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'event_id'          => $this->resource->event_id,
            'holder_name'       => $this->resource->holder_name,
            'holder_email'      => $this->resource->holder_email,
            'ticket_type'       => $this->resource->ticket_type,
            'seat'              => $this->resource->seat,
            'price'             => $this->resource->price,
            'status'            => $this->resource->status,
            'qr_code'           => $this->resource->qr_code,
            'tags'              => $this->resource->tags,
            'correlation_id'    => $this->resource->correlation_id,
            'created_at'        => $this->resource->created_at,
            'updated_at'        => $this->resource->updated_at,
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
