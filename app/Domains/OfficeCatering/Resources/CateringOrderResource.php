<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CateringOrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->resource->id,
            'uuid'             => $this->resource->uuid,
            'tenant_id'        => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'company_id'       => $this->resource->company_id,
            'status'           => $this->resource->status,
            'persons_count'    => $this->resource->persons_count,
            'menu_type'        => $this->resource->menu_type,
            'dietary_options'  => $this->resource->dietary_options,
            'delivery_at'      => $this->resource->delivery_at,
            'is_recurring'     => $this->resource->is_recurring,
            'total_amount'     => $this->resource->total_amount,
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
