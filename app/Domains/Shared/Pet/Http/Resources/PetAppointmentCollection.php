<?php

declare(strict_types=1);

namespace App\Domains\Pet\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

/**
 * Class PetAppointmentCollection
 *
 * Part of the Pet vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 */
final class PetAppointmentCollection extends ResourceCollection
{
    /**
     * Ресурс элемента коллекции.
     *
     * @var string
     */
    public $collects = PetAppointmentResource::class;

    /**
     * Трансформация коллекции в массив.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->collection->count(),
                'correlation_id' => $request->header('X-Correlation-ID', (string) Str::uuid()),
                'api_version' => 'v1',
            ],
        ];
    }
}
