<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\Food\Services;

use Psr\Log\LoggerInterface;

use App\Domains\Food\Models\Restaurant;
use App\Domains\Food\DTOs\SearchRestaurantDto;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Database\Eloquent\Collection;

final readonly class RestaurantCatalogService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LogManager $log) {}

    public function searchNearby(SearchRestaurantDto $dto): Collection
    {
        $this->fraud->check([
            'action' => 'search_restaurants',
            'lat' => $dto->lat,
            'lon' => $dto->lon,
            'correlation_id' => $dto->correlationId,
        ]);

        $query = Restaurant::query()
            ->with(['dishes' => function ($q) {
                $q->where('is_available', true);
            }])
            ->selectRaw(
                '*, (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lon) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance',
                [$dto->lat, $dto->lon, $dto->lat]
            )
            ->having('distance', '<', $dto->radiusKm)
            ->where('is_active', true);

        if (! empty($dto->query)) {
            $query->where('name', 'LIKE', '%'.$dto->query.'%');
        }

        $results = $query->orderBy('distance')->limit(50)->get();

        $this->log->channel('audit')->$this->logger->info('Restaurant catalog searched', [
            'results_count' => $results->count(),
            'correlation_id' => $dto->correlationId,
        ]);

        return $results;
    }

    public function getRestaurantMenu(int $restaurantId, string $correlationId): Collection
    {
        $restaurant = Restaurant::findOrFail($restaurantId);

        $this->log->channel('audit')->$this->logger->info('Fetched restaurant menu', [
            'restaurant_id' => $restaurantId,
            'correlation_id' => $correlationId,
        ]);

        return $restaurant->dishes()->where('is_available', true)->get();
    }
}
