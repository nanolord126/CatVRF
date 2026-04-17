<?php declare(strict_types=1);

namespace App\Domains\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InventoryCheckResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->resource->id,
            'uuid'             => $this->resource->uuid,
            'tenant_id'        => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'warehouse_id'     => $this->resource->warehouse_id,
            'type'             => $this->resource->type,
            'status'           => $this->resource->status,
            'scheduled_at'     => $this->resource->scheduled_at,
            'started_at'       => $this->resource->started_at,
            'completed_at'     => $this->resource->completed_at,
            'discrepancies'    => $this->resource->discrepancies,
            'employee_ids'     => $this->resource->employee_ids,
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
