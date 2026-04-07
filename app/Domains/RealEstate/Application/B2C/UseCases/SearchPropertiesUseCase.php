<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Application\B2C\UseCases;

use App\Domains\RealEstate\Application\B2C\DTOs\PropertyDTO;
use App\Domains\RealEstate\Application\B2C\DTOs\SearchPropertiesDTO;
use App\Domains\RealEstate\Domain\Services\PropertySearchServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class SearchPropertiesUseCase
{
    public function __construct(
        private readonly PropertySearchServiceInterface $searchService,
        private readonly LoggerInterface               $logger) {}

    /**
     * @return array{items: Collection<int, PropertyDTO>, total: int, page: int, per_page: int}
     */
    public function handle(SearchPropertiesDTO $dto, int $tenantId): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('RealEstate.SearchProperties started', [
            'correlation_id' => $correlationId,
            'tenant_id'      => $tenantId,
            'query'          => $dto->query,
            'type'           => $dto->type?->value,
            'lat'            => $dto->lat,
            'lon'            => $dto->lon,
            'radius_m'       => $dto->radiusMeters,
        ]);

        $properties = $this->searchService->search(
            query: $dto->query,
            type: $dto->type,
            minPriceKopecks: $dto->minPriceKopecks,
            maxPriceKopecks: $dto->maxPriceKopecks,
            minAreaSqm: $dto->minAreaSqm,
            rooms: $dto->rooms,
            lat: $dto->lat,
            lon: $dto->lon,
            radiusMeters: $dto->radiusMeters,
            tenantId: $tenantId,
            perPage: $dto->perPage,
            page: $dto->page,
        );

        $total = $this->searchService->count(
            query: $dto->query,
            type: $dto->type,
            minPriceKopecks: $dto->minPriceKopecks,
            maxPriceKopecks: $dto->maxPriceKopecks,
            minAreaSqm: $dto->minAreaSqm,
            rooms: $dto->rooms,
            lat: $dto->lat,
            lon: $dto->lon,
            radiusMeters: $dto->radiusMeters,
            tenantId: $tenantId,
        );

        $items = $properties->map(
            static fn ($property): PropertyDTO => PropertyDTO::fromEntity($property)
        );

        $this->logger->info('RealEstate.SearchProperties completed', [
            'correlation_id' => $correlationId,
            'total_found'    => $total,
        ]);

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $dto->page,
            'per_page' => $dto->perPage,
        ];
    }
}
