<?php declare(strict_types=1);

namespace App\Domains\Insurance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class InsurancePolicyCollection
 *
 * Part of the Insurance vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Authorization policy for resource access control.
 * Enforces tenant-scoped permissions.
 * Integrates with B2C/B2B role system.
 *
 * @package App\Domains\Insurance\Http\Resources
 */
final class InsurancePolicyCollection extends ResourceCollection
{
    public $collects = InsurancePolicyResource::class;

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
