<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Resources\B2B;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

/**
 * B2B API Collection: Appointments.
 *
 * CANON 2026 — Layer 8: Resources (B2B namespace).
 */
final class AppointmentCollection extends ResourceCollection
{
    public $collects = AppointmentResource::class;

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
                'resource_type'  => 'appointment_collection',
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
