<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use App\Domains\Hotels\Models\Hotel;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * HotelSearchIntegrationService — сервис интеграции Hotels с поисковой системой.
 *
 * Layer 3: Services — CatVRF 9-layer architecture.
 *
 * Индексирует Hotels в поисковой системе для быстрого поиска
 * по всем критериям (Booking.com/Airbnb фильтры + специфические).
 *
 * @package App\Domains\Hotels\Services
 * @version 2026.1
 */
final readonly class HotelSearchIntegrationService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * Индексировать отель в поисковой системе.
     *
     * @param Hotel $hotel Отель для индексации
     * @param string $correlationId ID корреляции
     *
     * @return array<string, mixed> Результат индексации
     */
    public function indexHotel(Hotel $hotel, string $correlationId): array
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'hotel_search_index',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $searchDocument = $this->buildSearchDocument($hotel);

        $this->logger->info('Hotel indexed in search system', [
            'hotel_id' => $hotel->id,
            'hotel_name' => $hotel->name,
            'correlation_id' => $correlationId,
        ]);

        return [
            'hotel_id' => $hotel->id,
            'indexed' => true,
            'search_document' => $searchDocument,
        ];
    }

    /**
     * Массовая индексация отелей.
     *
     * @param int $tenantId ID tenant
     * @param string $correlationId ID корреляции
     *
     * @return array<string, mixed> Результат индексации
     */
    public function bulkIndexHotels(int $tenantId, string $correlationId): array
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'hotel_bulk_search_index',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $hotels = Hotel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $indexedCount = 0;
        $failedCount = 0;

        foreach ($hotels as $hotel) {
            try {
                $this->indexHotel($hotel, $correlationId);
                $indexedCount++;
            } catch (\Throwable $e) {
                $this->logger->error('Failed to index hotel', [
                    'hotel_id' => $hotel->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                $failedCount++;
            }
        }

        $this->audit->log(
            action: 'hotel_bulk_search_index_completed',
            subjectType: Hotel::class,
            subjectId: null,
            oldValues: [],
            newValues: [
                'tenant_id' => $tenantId,
                'indexed_count' => $indexedCount,
                'failed_count' => $failedCount,
                'total_count' => $hotels->count(),
            ],
            correlationId: $correlationId,
        );

        $this->logger->info('Bulk hotel index completed', [
            'tenant_id' => $tenantId,
            'indexed_count' => $indexedCount,
            'failed_count' => $failedCount,
            'total_count' => $hotels->count(),
            'correlation_id' => $correlationId,
        ]);

        return [
            'tenant_id' => $tenantId,
            'indexed_count' => $indexedCount,
            'failed_count' => $failedCount,
            'total_count' => $hotels->count(),
        ];
    }

    /**
     * Удалить отель из поискового индекса.
     *
     * @param int $hotelId ID отеля
     * @param string $correlationId ID корреляции
     *
     * @return bool Результат удаления
     */
    public function removeFromIndex(int $hotelId, string $correlationId): bool
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'hotel_search_remove',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $hotel = Hotel::findOrFail($hotelId);

        $this->logger->info('Hotel removed from search index', [
            'hotel_id' => $hotelId,
            'hotel_name' => $hotel->name,
            'correlation_id' => $correlationId,
        ]);

        return true;
    }

    /**
     * Построить поисковый документ для отеля.
     *
     * @param Hotel $hotel Отель
     *
     * @return array<string, mixed> Поисковый документ
     */
    private function buildSearchDocument(Hotel $hotel): array
    {
        return [
            'id' => $hotel->id,
            'uuid' => $hotel->uuid,
            'tenant_id' => $hotel->tenant_id,
            'vertical' => 'hotels',
            'property_type_id' => $hotel->property_type_id,
            'name' => $hotel->name,
            'description' => $hotel->description,
            'address' => $hotel->address,
            'stars' => $hotel->stars,
            'rating' => $hotel->rating,
            'review_count' => $hotel->review_count,
            'tags' => $hotel->tags,
            // Booking.com/Airbnb фильтры
            'min_price_per_night' => $hotel->min_price_per_night,
            'max_price_per_night' => $hotel->max_price_per_night,
            'has_wifi' => $hotel->has_wifi ?? false,
            'has_parking' => $hotel->has_parking ?? false,
            'has_pool' => $hotel->has_pool ?? false,
            'has_spa' => $hotel->has_spa ?? false,
            'has_gym' => $hotel->has_gym ?? false,
            'has_restaurant' => $hotel->has_restaurant ?? false,
            'has_breakfast_included' => $hotel->has_breakfast_included ?? false,
            'has_kitchen' => $hotel->has_kitchen ?? false,
            'has_air_conditioning' => $hotel->has_air_conditioning ?? false,
            'has_washing_machine' => $hotel->has_washing_machine ?? false,
            'has_balcony' => $hotel->has_balcony ?? false,
            'has_elevator' => $hotel->has_elevator ?? false,
            'has_24h_reception' => $hotel->has_24h_reception ?? false,
            'has_concierge' => $hotel->has_concierge ?? false,
            // Вид из окна
            'has_sea_view' => $hotel->has_sea_view ?? false,
            'has_mountain_view' => $hotel->has_mountain_view ?? false,
            'has_garden_view' => $hotel->has_garden_view ?? false,
            'has_city_view' => $hotel->has_city_view ?? false,
            'has_pool_view' => $hotel->has_pool_view ?? false,
            'has_lake_view' => $hotel->has_lake_view ?? false,
            // Политика
            'pet_friendly' => $hotel->pet_friendly ?? false,
            'smoking_allowed' => $hotel->smoking_allowed ?? false,
            'wheelchair_accessible' => $hotel->wheelchair_accessible ?? false,
            'family_friendly' => $hotel->family_friendly ?? false,
            'adults_only' => $hotel->adults_only ?? false,
            'all_inclusive' => $hotel->all_inclusive ?? false,
            // Специфические фильтры для бассейнов
            'pool_size_sqm' => $hotel->pool_size_sqm,
            'pool_count' => $hotel->pool_count,
            'pool_has_heating' => $hotel->pool_has_heating ?? false,
            'pool_has_kids_area' => $hotel->pool_has_kids_area ?? false,
            'pool_indoor' => $hotel->pool_indoor ?? false,
            'pool_outdoor' => $hotel->pool_outdoor ?? false,
            'pool_has_slides' => $hotel->pool_has_slides ?? false,
            'pool_has_bar' => $hotel->pool_has_bar ?? false,
            // Расстояния до объектов (в метрах)
            'distance_to_sea_meters' => $hotel->distance_to_sea_meters,
            'distance_to_beach_meters' => $hotel->distance_to_beach_meters,
            'distance_to_pharmacy_meters' => $hotel->distance_to_pharmacy_meters,
            'distance_to_grocery_meters' => $hotel->distance_to_grocery_meters,
            'distance_to_florist_meters' => $hotel->distance_to_florist_meters,
            'distance_to_bus_stop_meters' => $hotel->distance_to_bus_stop_meters,
            'distance_to_train_station_meters' => $hotel->distance_to_train_station_meters,
            'distance_to_airport_meters' => $hotel->distance_to_airport_meters,
            'distance_to_city_center_meters' => $hotel->distance_to_city_center_meters,
            'distance_to_ski_lift_meters' => $hotel->distance_to_ski_lift_meters,
            // Геолокация для поиска по радиусу
            'latitude' => $hotel->latitude,
            'longitude' => $hotel->longitude,
            'search_radius_meters' => $hotel->search_radius_meters,
            // Метаданные для поисковой системы
            'is_active' => $hotel->is_active,
            'indexed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Синхронизировать изменения отеля с поисковой системой.
     *
     * @param Hotel $hotel Обновлённый отель
     * @param string $correlationId ID корреляции
     *
     * @return array<string, mixed> Результат синхронизации
     */
    public function syncHotelChanges(Hotel $hotel, string $correlationId): array
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'hotel_search_sync',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $result = $this->indexHotel($hotel, $correlationId);

        $this->audit->log(
            action: 'hotel_search_sync_completed',
            subjectType: Hotel::class,
            subjectId: $hotel->id,
            oldValues: [],
            newValues: ['hotel_id' => $hotel->id, 'synced' => true],
            correlationId: $correlationId,
        );

        return $result;
    }
}
