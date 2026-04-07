<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class HotelBookingCollection
 *
 * Part of the Hotels vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Hotels\Http\Resources
 */
final class HotelBookingCollection extends ResourceCollection
{
    private $collects = HotelBookingResource::class;

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
                'correlation_id' => $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid()),
            ],
        ];
    }
}
