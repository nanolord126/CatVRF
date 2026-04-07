<?php

declare(strict_types=1);

/**
 * SearchHotelsUseCase — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/searchhotelsusecase
 */


namespace App\Domains\Hotels\Application\UseCases\B2C;

use App\Domains\Hotels\Domain\Repositories\HotelRepositoryInterface;
use App\Domains\Hotels\Application\DTO\HotelDTO;
use Illuminate\Support\Collection;

/**
 * Class SearchHotelsUseCase
 *
 * Part of the Hotels vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Hotels\Application\UseCases\B2C
 */
final class SearchHotelsUseCase
{
    public function __construct(
        private readonly HotelRepositoryInterface $hotelRepository
    ) {
    }

    /**
     * @param array $criteria
     * @return Collection<HotelDTO>
     */
    public function __invoke(array $criteria): Collection
    {
        $hotels = $this->hotelRepository->search($criteria);

        return $hotels->map(fn ($hotel) => HotelDTO::from($hotel->toArray()));
    }
}
