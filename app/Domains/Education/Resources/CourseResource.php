<?php declare(strict_types=1);

namespace App\Domains\Education\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CourseResource extends JsonResource
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
            'description'      => $this->resource->description,
            'category'         => $this->resource->category,
            'format'           => $this->resource->format,
            'level'            => $this->resource->level,
            'price'            => $this->resource->price,
            'duration_hours'   => $this->resource->duration_hours,
            'students_count'   => $this->resource->students_count ?? 0,
            'rating'           => $this->resource->rating,
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
