<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EventResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->resource->id,
            'uuid'             => $this->resource->uuid,
            'tenant_id'        => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'title'            => $this->resource->title,
            'type'             => $this->resource->type,
            'venue_address'    => $this->resource->venue_address,
            'guests_count'     => $this->resource->guests_count,
            'budget'           => $this->resource->budget,
            'status'           => $this->resource->status,
            'organizer_id'     => $this->resource->organizer_id,
            'starts_at'        => $this->resource->starts_at,
            'ends_at'          => $this->resource->ends_at,
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
