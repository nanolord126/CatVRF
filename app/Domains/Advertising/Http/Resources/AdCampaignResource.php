<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * AdCampaignResource — API Resource for ad campaign responses.
 *
 * Formats model data for API output.
 * Always includes correlation_id in meta.
 *
 * @package App\Domains\Advertising\Http\Resources
 */
final class AdCampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'budget' => $this->budget,
            'spent' => $this->spent,
            'pricing_model' => $this->pricing_model,
            'targeting_criteria' => $this->targeting_criteria,
            'status' => $this->status,
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'correlation_id' => $this->correlation_id,
            'tenant_id' => $this->tenant_id,
            'business_group_id' => $this->business_group_id,
            'tags' => $this->tags,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->header(
                    'X-Correlation-ID',
                    (string) Str::uuid(),
                ),
                'api_version' => 'v1',
                'resource_type' => 'ad_campaign',
            ],
        ];
    }
}
