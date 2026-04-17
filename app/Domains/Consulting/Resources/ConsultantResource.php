<?php declare(strict_types=1);

namespace App\Domains\Consulting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConsultantResource extends JsonResource
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
            'specialization'   => $this->resource->specialization,
            'experience_years' => $this->resource->experience_years,
            'hourly_rate'      => $this->resource->hourly_rate,
            'languages'        => $this->resource->languages,
            'rating'           => $this->resource->rating,
            'reviews_count'    => $this->resource->reviews_count,
            'is_active'        => $this->resource->is_active,
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
