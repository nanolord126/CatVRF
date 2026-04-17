<?php declare(strict_types=1);

namespace App\Domains\PromoCampaigns\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PromoCampaignResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->resource->id,
            'uuid'              => $this->resource->uuid,
            'tenant_id'         => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'name'              => $this->resource->name,
            'type'              => $this->resource->type,
            'discount_percent'  => $this->resource->discount_percent,
            'cashback_percent'  => $this->resource->cashback_percent,
            'conditions'        => $this->resource->conditions,
            'min_order_amount'  => $this->resource->min_order_amount,
            'max_uses'          => $this->resource->max_uses,
            'uses_count'        => $this->resource->uses_count ?? 0,
            'status'            => $this->resource->status,
            'starts_at'         => $this->resource->starts_at,
            'ends_at'           => $this->resource->ends_at,
            'verticals'         => $this->resource->verticals,
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
