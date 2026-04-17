<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use App\Domains\Hotels\DTOs\HotelSearchFilterDto;
use App\Domains\Hotels\Models\HotelSearchFilter;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * HotelSearchFilterService — сервис управления поисковыми фильтрами Hotels.
 *
 * Layer 3: Services — CatVRF 9-layer architecture.
 *
 * Управляет фильтрами аналогичными Booking.com и Airbnb,
 * плюс специфические фильтры для бассейнов и расстояний.
 *
 * @package App\Domains\Hotels\Services
 * @version 2026.1
 */
final readonly class HotelSearchFilterService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * Создать или обновить фильтры для отеля.
     *
     * @param HotelSearchFilterDto $dto DTO с фильтрами
     * @param string $correlationId ID корреляции
     *
     * @return HotelSearchFilter Созданные или обновлённые фильтры
     *
     * @throws \DomainException
     */
    public function save(HotelSearchFilterDto $dto, string $correlationId): HotelSearchFilter
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'hotel_search_filter_save',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($dto, $correlationId) {
            $existingFilter = null;

            if ($dto->hotelId !== null) {
                $existingFilter = HotelSearchFilter::where('hotel_id', $dto->hotelId)
                    ->where('tenant_id', $dto->tenantId)
                    ->first();
            }

            $filterData = [
                'tenant_id' => $dto->tenantId,
                'hotel_id' => $dto->hotelId,
                // Booking.com/Airbnb фильтры
                'min_stars' => $dto->minStars,
                'max_stars' => $dto->maxStars,
                'min_rating' => $dto->minRating,
                'max_rating' => $dto->maxRating,
                'min_price' => $dto->minPrice,
                'max_price' => $dto->maxPrice,
                'has_wifi' => $dto->hasWifi,
                'has_parking' => $dto->hasParking,
                'has_pool' => $dto->hasPool,
                'has_spa' => $dto->hasSpa,
                'has_gym' => $dto->hasGym,
                'has_restaurant' => $dto->hasRestaurant,
                'has_breakfast_included' => $dto->hasBreakfastIncluded,
                'has_kitchen' => $dto->hasKitchen,
                'has_air_conditioning' => $dto->hasAirConditioning,
                'has_washing_machine' => $dto->hasWashingMachine,
                'has_balcony' => $dto->hasBalcony,
                'has_sea_view' => $dto->hasSeaView,
                'has_mountain_view' => $dto->hasMountainView,
                'has_garden_view' => $dto->hasGardenView,
                'has_city_view' => $dto->hasCityView,
                'pet_friendly' => $dto->petFriendly,
                'smoking_allowed' => $dto->smokingAllowed,
                'wheelchair_accessible' => $dto->wheelchairAccessible,
                'family_friendly' => $dto->familyFriendly,
                'adults_only' => $dto->adultsOnly,
                // Специфические фильтры для бассейнов
                'pool_size_sqm' => $dto->poolSizeSqm,
                'pool_count' => $dto->poolCount,
                'pool_has_heating' => $dto->poolHasHeating,
                'pool_has_kids_area' => $dto->poolHasKidsArea,
                'pool_indoor' => $dto->poolIndoor,
                'pool_outdoor' => $dto->poolOutdoor,
                // Расстояния до объектов (в метрах)
                'distance_to_sea_meters' => $dto->distanceToSeaMeters,
                'distance_to_pharmacy_meters' => $dto->distanceToPharmacyMeters,
                'distance_to_grocery_meters' => $dto->distanceToGroceryMeters,
                'distance_to_florist_meters' => $dto->distanceToFloristMeters,
                'distance_to_beach_meters' => $dto->distanceToBeachMeters,
                'distance_to_bus_stop_meters' => $dto->distanceToBusStopMeters,
                'distance_to_train_station_meters' => $dto->distanceToTrainStationMeters,
                'distance_to_airport_meters' => $dto->distanceToAirportMeters,
                // Дополнительные фильтры
                'check_in_from' => $dto->checkInFrom,
                'check_in_to' => $dto->checkInTo,
                'check_out_from' => $dto->checkOutFrom,
                'check_out_to' => $dto->checkOutTo,
                'guests_count' => $dto->guestsCount,
                'rooms_count' => $dto->roomsCount,
                'property_types' => $dto->propertyTypes,
                'amenities' => $dto->amenities,
                'correlation_id' => $correlationId,
            ];

            if ($existingFilter !== null) {
                $oldData = $existingFilter->toArray();
                $existingFilter->update($filterData);
                $filter = $existingFilter->fresh();

                $this->audit->log(
                    action: 'hotel_search_filter_updated',
                    subjectType: HotelSearchFilter::class,
                    subjectId: $filter->id,
                    oldValues: $oldData,
                    newValues: $filter->toArray(),
                    correlationId: $correlationId,
                );

                $this->logger->info('Hotel search filter updated', [
                    'filter_id' => $filter->id,
                    'hotel_id' => $filter->hotel_id,
                    'correlation_id' => $correlationId,
                ]);
            } else {
                $filterData['uuid'] = Str::uuid()->toString();
                $filter = HotelSearchFilter::create($filterData);

                $this->audit->log(
                    action: 'hotel_search_filter_created',
                    subjectType: HotelSearchFilter::class,
                    subjectId: $filter->id,
                    oldValues: [],
                    newValues: $filter->toArray(),
                    correlationId: $correlationId,
                );

                $this->logger->info('Hotel search filter created', [
                    'filter_id' => $filter->id,
                    'hotel_id' => $filter->hotel_id,
                    'correlation_id' => $correlationId,
                ]);
            }

            return $filter;
        });
    }

    /**
     * Получить фильтры для отеля.
     *
     * @param int $hotelId ID отеля
     * @param int $tenantId ID tenant
     * @param string $correlationId ID корреляции
     *
     * @return HotelSearchFilter|null Фильтры отеля
     */
    public function getByHotel(int $hotelId, int $tenantId, string $correlationId): ?HotelSearchFilter
    {
        $filter = HotelSearchFilter::where('hotel_id', $hotelId)
            ->where('tenant_id', $tenantId)
            ->first();

        $this->logger->info('Hotel search filter retrieved', [
            'hotel_id' => $hotelId,
            'filter_exists' => $filter !== null,
            'correlation_id' => $correlationId,
        ]);

        return $filter;
    }

    /**
     * Удалить фильтры для отеля.
     *
     * @param int $hotelId ID отеля
     * @param int $tenantId ID tenant
     * @param string $correlationId ID корреляции
     *
     * @return bool Результат удаления
     */
    public function deleteByHotel(int $hotelId, int $tenantId, string $correlationId): bool
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'hotel_search_filter_delete',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($hotelId, $tenantId, $correlationId) {
            $filter = HotelSearchFilter::where('hotel_id', $hotelId)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($filter === null) {
                return true;
            }

            $oldData = $filter->toArray();

            $filter->delete();

            $this->audit->log(
                action: 'hotel_search_filter_deleted',
                subjectType: HotelSearchFilter::class,
                subjectId: $filter->id,
                oldValues: $oldData,
                newValues: [],
                correlationId: $correlationId,
            );

            $this->logger->info('Hotel search filter deleted', [
                'filter_id' => $filter->id,
                'hotel_id' => $hotelId,
                'correlation_id' => $correlationId,
            ]);

            return true;
        });
    }

    /**
     * Поиск отелей по фильтрам.
     *
     * @param HotelSearchFilterDto $dto DTO с фильтрами поиска
     * @param string $correlationId ID корреляции
     *
     * @return array<int, array<string, mixed>> Результаты поиска
     */
    public function search(HotelSearchFilterDto $dto, string $correlationId): array
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'hotel_search',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $query = \App\Domains\Hotels\Models\Hotel::where('tenant_id', $dto->tenantId)
            ->where('is_active', true);

        // Booking.com/Airbnb фильтры
        if ($dto->minStars !== null) {
            $query->where('stars', '>=', $dto->minStars);
        }

        if ($dto->maxStars !== null) {
            $query->where('stars', '<=', $dto->maxStars);
        }

        if ($dto->minRating !== null) {
            $query->where('rating', '>=', $dto->minRating);
        }

        if ($dto->maxRating !== null) {
            $query->where('rating', '<=', $dto->maxRating);
        }

        // Фильтры по удобствам (через search filters)
        if ($dto->hasPool || $dto->hasSeaView || $dto->hasSpa) {
            $query->whereHas('searchFilter', function ($q) use ($dto) {
                if ($dto->hasPool) {
                    $q->where('has_pool', true);
                }
                if ($dto->hasSeaView) {
                    $q->where('has_sea_view', true);
                }
                if ($dto->hasSpa) {
                    $q->where('has_spa', true);
                }
            });
        }

        // Специфические фильтры для бассейнов
        if ($dto->poolSizeSqm !== null) {
            $query->whereHas('searchFilter', function ($q) use ($dto) {
                $q->where('pool_size_sqm', '>=', $dto->poolSizeSqm);
            });
        }

        if ($dto->poolHasHeating) {
            $query->whereHas('searchFilter', function ($q) {
                $q->where('pool_has_heating', true);
            });
        }

        if ($dto->poolHasKidsArea) {
            $query->whereHas('searchFilter', function ($q) {
                $q->where('pool_has_kids_area', true);
            });
        }

        // Расстояния до объектов (в метрах)
        if ($dto->distanceToSeaMeters !== null) {
            $query->whereHas('searchFilter', function ($q) use ($dto) {
                $q->where('distance_to_sea_meters', '<=', $dto->distanceToSeaMeters);
            });
        }

        if ($dto->distanceToPharmacyMeters !== null) {
            $query->whereHas('searchFilter', function ($q) use ($dto) {
                $q->where('distance_to_pharmacy_meters', '<=', $dto->distanceToPharmacyMeters);
            });
        }

        if ($dto->distanceToGroceryMeters !== null) {
            $query->whereHas('searchFilter', function ($q) use ($dto) {
                $q->where('distance_to_grocery_meters', '<=', $dto->distanceToGroceryMeters);
            });
        }

        if ($dto->distanceToFloristMeters !== null) {
            $query->whereHas('searchFilter', function ($q) use ($dto) {
                $q->where('distance_to_florist_meters', '<=', $dto->distanceToFloristMeters);
            });
        }

        if ($dto->distanceToBeachMeters !== null) {
            $query->whereHas('searchFilter', function ($q) use ($dto) {
                $q->where('distance_to_beach_meters', '<=', $dto->distanceToBeachMeters);
            });
        }

        // Типы размещения
        if ($dto->propertyTypes !== null && count($dto->propertyTypes) > 0) {
            $query->whereHas('propertyType', function ($q) use ($dto) {
                $q->whereIn('slug', $dto->propertyTypes);
            });
        }

        $hotels = $query->with(['propertyType', 'searchFilter'])
            ->get()
            ->toArray();

        $this->logger->info('Hotel search completed', [
            'tenant_id' => $dto->tenantId,
            'results_count' => count($hotels),
            'correlation_id' => $correlationId,
        ]);

        return $hotels;
    }
}
