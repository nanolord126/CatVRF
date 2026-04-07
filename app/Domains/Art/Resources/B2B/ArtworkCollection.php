<?php

declare(strict_types=1);

namespace App\Domains\Art\Resources\B2B;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

/**
 * B2B API Collection: пагинированная коллекция произведений.
 *
 * CANON 2026 — Layer 8: Resources (B2B namespace).
 * Оборачивает коллекцию ArtworkResource с пагинацией и meta-данными.
 * Всегда включает correlation_id, tenant_id, пагинацию.
 *
 * @package App\Domains\Art\Resources\B2B
 */
final class ArtworkCollection extends ResourceCollection
{
    /**
     * Тип ресурса для каждого элемента коллекции.
     *
     * @var string
     */
    public $collects = ArtworkResource::class;

    /**
     * Трансформация коллекции.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    /**
     * Мета-данные и информация о пагинации.
     *
     * @param  Request $request
     * @param  array<string, mixed> $paginated
     * @param  array<string, mixed> $default
     * @return array<string, mixed>
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->header('X-Correlation-ID', (string) Str::uuid()),
                'api_version'    => 'v1',
                'resource_type'  => 'artwork_collection',
                'tenant_id'      => $this->collection->first()?->tenant_id,
                'total'          => $paginated['total'] ?? $this->collection->count(),
                'per_page'       => $paginated['per_page'] ?? 20,
                'current_page'   => $paginated['current_page'] ?? 1,
                'last_page'      => $paginated['last_page'] ?? 1,
            ],
            'links' => $default['links'] ?? [],
        ];
    }
}
