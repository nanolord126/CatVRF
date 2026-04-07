<?php declare(strict_types=1);

/**
 * Class HomeServiceJobCollection
 *
 * Part of the HomeServices vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\HomeServices\Http\Resources
 */
final class HomeServiceJobCollection extends ResourceCollection
{
    /**
     * Ресурс элемента коллекции.
     *
     * @var string
     */
    private $collects = HomeServiceJobResource::class;

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
