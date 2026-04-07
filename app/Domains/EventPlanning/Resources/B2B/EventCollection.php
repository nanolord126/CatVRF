<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Resources\B2B;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

/**
 * B2B API Collection: Events.
 *
 * CANON 2026 — Layer 8: Resources (B2B namespace).
 */
final class EventCollection extends ResourceCollection
{
    public $collects = EventResource::class;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->header('X-Correlation-ID', (string) Str::uuid()),
                'api_version'    => 'v1',
                'resource_type'  => 'event_collection',
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
