<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Common API Resource Pattern for 2026 Ecosystem.
 * Consistent JSON structure across all Partner API endpoints.
 */
class EcosystemApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => get_class($this->resource),
            'attributes' => parent::toArray($request),
            'meta' => [
                'correlation_id' => $this->correlation_id ?? 'N/A',
                'api_version' => 'v2026.1-beta',
                'server_time' => Carbon::now(),
            ],
            'links' => [
                'self' => url()->current(),
            ]
        ];
    }
}
