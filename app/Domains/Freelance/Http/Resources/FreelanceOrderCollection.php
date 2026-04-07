<?php declare(strict_types=1);

namespace App\Domains\Freelance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class FreelanceOrderCollection
 *
 * Part of the Freelance vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Freelance\Http\Resources
 */
final class FreelanceOrderCollection extends ResourceCollection
{
    /**
     * Ресурс элемента коллекции.
     *
     * @var string
     */
    private $collects = FreelanceOrderResource::class;

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
                'correlation_id' => $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid()),
                'api_version' => 'v1',
            ],
        ];
    }
}
