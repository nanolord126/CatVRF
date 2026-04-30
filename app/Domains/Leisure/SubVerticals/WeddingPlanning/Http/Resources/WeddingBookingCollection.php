<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\WeddingPlanning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

/**
 * Class WeddingBookingCollection
 *
 * Part of the WeddingPlanning vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 */
final class WeddingBookingCollection extends ResourceCollection
{
    public $collects = WeddingBookingResource::class;

    /**
     * Handle toArray operation.
     *
     * @throws \DomainException
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->collection->count(),
                'correlation_id' => $request->header('X-Correlation-ID', (string) Str::uuid()),
            ],
        ];
    }
}
