<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use Illuminate\Support\Facades\Log;

use App\Domains\RealEstate\Models\Property;

/**
 * Service для поиска и фильтрации объектов недвижимости.
 * Production 2026.
 */
final class PropertySearchService
{
    public function searchProperties(array $filters, string $correlationId = ''): mixed
    {


        try {
            $this->log->channel('audit')->info('Searching properties', [
                'filters' => $filters,
                'correlation_id' => $correlationId,
            ]);

            $query = Property::query()
                ->where('status', 'active');

            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            }

            if (!empty($filters['min_area'])) {
                $query->where('area', '>=', $filters['min_area']);
            }

            if (!empty($filters['rooms'])) {
                $query->where('rooms', $filters['rooms']);
            }

            if (!empty($filters['search'])) {
                $query->where('address', 'ILIKE', '%' . $filters['search'] . '%');
            }

            $results = $query->with(['rentalListing', 'saleListing', 'images'])
                ->paginate($filters['per_page'] ?? 20);

            $this->log->channel('audit')->info('Properties found', [
                'count' => $results->count(),
                'correlation_id' => $correlationId,
            ]);

            return $results;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Property search failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function getPropertyDetails(Property $property, string $correlationId = ''): array
    {


        return [
            'property' => $property->load(['rentalListing', 'saleListing', 'images', 'viewingAppointments']),
            'stats' => [
                'view_count' => $property->view_count ?? 0,
                'viewing_count' => $property->viewingAppointments()->count(),
            ],
        ];
    }
}
