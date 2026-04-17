<?php declare(strict_types=1);

namespace App\Domains\Advertising\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AdCampaignResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->resource->id,
            'uuid'             => $this->resource->uuid,
            'tenant_id'        => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'name'             => $this->resource->name,
            'type'             => $this->resource->type,
            'status'           => $this->resource->status,
            'budget'           => $this->resource->budget,
            'spent'            => $this->resource->spent,
            'targeting'        => $this->resource->targeting,
            'starts_at'        => $this->resource->starts_at,
            'ends_at'          => $this->resource->ends_at,
            'impressions'      => $this->resource->impressions ?? 0,
            'clicks'           => $this->resource->clicks ?? 0,
            'ctr'              => $this->resource->ctr ?? 0,
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
