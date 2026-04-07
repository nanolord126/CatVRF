<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class FarmProductResource
 *
 * Part of the FarmDirect vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * API Resource for response transformation.
 * Formats model data for API responses.
 * Always includes correlation_id in meta.
 *
 * @package App\Domains\FarmDirect\Http\Resources
 */
final class FarmProductResource extends JsonResource
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
            'name' => $this->name,
            'farm_id' => $this->farm_id,
            'category' => $this->category,
            'unit' => $this->unit,
            'price_per_unit' => $this->price_per_unit,
            'quantity' => $this->quantity,
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
            ],
        ];
    }
}
