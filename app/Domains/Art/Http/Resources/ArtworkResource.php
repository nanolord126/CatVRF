<?php declare(strict_types=1);

namespace App\Domains\Art\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ArtworkResource
 *
 * Part of the Art vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * API Resource for response transformation.
 * Formats model data for API responses.
 * Always includes correlation_id in meta.
 *
 * @package App\Domains\Art\Http\Resources
 */
final class ArtworkResource extends JsonResource
{
    /**
     * Handle toArray operation.
     *
     * @throws \DomainException
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'artist_id' => $this->artist_id,
            'technique' => $this->technique,
            'dimensions' => $this->dimensions,
            'price' => $this->price,
            'description' => $this->description,
            'correlation_id' => $this->correlation_id,
            'tenant_id' => $this->tenant_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Handle with operation.
     *
     * @throws \DomainException
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid()),
                'api_version' => 'v1',
                'resource_type' => 'artwork',
            ],
        ];
    }
}
