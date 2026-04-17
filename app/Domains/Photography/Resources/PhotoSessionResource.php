<?php declare(strict_types=1);

namespace App\Domains\Photography\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PhotoSessionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->resource->id,
            'uuid'             => $this->resource->uuid,
            'tenant_id'        => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'photographer_id'  => $this->resource->photographer_id,
            'session_type'     => $this->resource->session_type,
            'location'         => $this->resource->location,
            'duration_hours'   => $this->resource->duration_hours,
            'scheduled_at'     => $this->resource->scheduled_at,
            'photos_count'     => $this->resource->photos_count,
            'retouching'       => $this->resource->retouching,
            'status'           => $this->resource->status,
            'price'            => $this->resource->price,
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
