<?php declare(strict_types=1);

namespace App\Domains\Analytics\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AnalyticsEventResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->resource->id,
            'uuid'           => $this->resource->uuid,
            'tenant_id'      => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'event_type'     => $this->resource->event_type,
            'vertical'       => $this->resource->vertical,
            'action'         => $this->resource->action,
            'user_id'        => $this->resource->user_id,
            'session_id'     => $this->resource->session_id,
            'payload'        => $this->resource->payload,
            'ip_address'     => $this->resource->ip_address,
            'device_type'    => $this->resource->device_type,
            'tags'           => $this->resource->tags,
            'correlation_id' => $this->resource->correlation_id,
            'created_at'     => $this->resource->created_at,
            'updated_at'     => $this->resource->updated_at,
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
