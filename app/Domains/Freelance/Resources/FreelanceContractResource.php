<?php declare(strict_types=1);

namespace App\Domains\Freelance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FreelanceContractResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->resource->id,
            'uuid'             => $this->resource->uuid,
            'tenant_id'        => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'freelancer_id'    => $this->resource->freelancer_id,
            'client_id'        => $this->resource->client_id,
            'title'            => $this->resource->title,
            'category'         => $this->resource->category,
            'price'            => $this->resource->price,
            'price_type'       => $this->resource->price_type,
            'status'           => $this->resource->status,
            'deadline_days'    => $this->resource->deadline_days,
            'skills'           => $this->resource->skills,
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
