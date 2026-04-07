<?php declare(strict_types=1);

namespace App\Domains\Photography\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class PhotoBookingResource
 *
 * Part of the Photography vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * API Resource for response transformation.
 * Formats model data for API responses.
 * Always includes correlation_id in meta.
 *
 * @package App\Domains\Photography\Http\Resources
 */
final class PhotoBookingResource extends JsonResource
{
    /**
     * Трансформация ресурса в массив.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'photographer_id' => $this->photographer_id,
            'studio_id' => $this->studio_id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'package_type' => $this->package_type,
            'total_price' => $this->total_price,
            'correlation_id' => $this->correlation_id,
            'tenant_id' => $this->tenant_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Дополнительные мета-данные ответа.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid()),
                'api_version' => 'v1',
            ],
        ];
    }
}
