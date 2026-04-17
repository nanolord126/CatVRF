<?php declare(strict_types=1);

namespace App\Domains\Staff\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StaffMemberResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->resource->id,
            'uuid'             => $this->resource->uuid,
            'tenant_id'        => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'user_id'          => $this->resource->user_id,
            'full_name'        => $this->resource->full_name,
            'position'         => $this->resource->position,
            'employment_type'  => $this->resource->employment_type,
            'phone'            => $this->resource->phone,
            'email'            => $this->resource->email,
            'vertical'         => $this->resource->vertical,
            'is_active'        => $this->resource->is_active,
            'hire_date'        => $this->resource->hire_date,
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
