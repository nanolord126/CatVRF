<?php

declare(strict_types=1);

namespace App\Domains\Hotels\DTOs;

use Illuminate\Http\Request;

/**
 * HotelSearchFilterDto — DTO для поисковых фильтров Hotels.
 *
 * CatVRF 9-layer architecture — Layer 2: DTOs.
 *
 * Содержит фильтры аналогичные Booking.com и Airbnb,
 * плюс специфические фильтры для бассейнов и расстояний.
 *
 * @package App\Domains\Hotels\DTOs
 */
final readonly class HotelSearchFilterDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public ?int $hotelId,
        // Booking.com/Airbnb фильтры
        public ?int $minStars,
        public ?int $maxStars,
        public ?float $minRating,
        public ?float $maxRating,
        public ?int $minPrice,
        public ?int $maxPrice,
        public bool $hasWifi,
        public bool $hasParking,
        public bool $hasPool,
        public bool $hasSpa,
        public bool $hasGym,
        public bool $hasRestaurant,
        public bool $hasBreakfastIncluded,
        public bool $hasKitchen,
        public bool $hasAirConditioning,
        public bool $hasWashingMachine,
        public bool $hasBalcony,
        public bool $hasSeaView,
        public bool $hasMountainView,
        public bool $hasGardenView,
        public bool $hasCityView,
        public bool $petFriendly,
        public bool $smokingAllowed,
        public bool $wheelchairAccessible,
        public bool $familyFriendly,
        public bool $adultsOnly,
        // Специфические фильтры для бассейнов
        public ?int $poolSizeSqm,
        public ?int $poolCount,
        public bool $poolHasHeating,
        public bool $poolHasKidsArea,
        public bool $poolIndoor,
        public bool $poolOutdoor,
        // Расстояния до объектов (в метрах)
        public ?int $distanceToSeaMeters,
        public ?int $distanceToPharmacyMeters,
        public ?int $distanceToGroceryMeters,
        public ?int $distanceToFloristMeters,
        public ?int $distanceToBeachMeters,
        public ?int $distanceToBusStopMeters,
        public ?int $distanceToTrainStationMeters,
        public ?int $distanceToAirportMeters,
        // Дополнительные фильтры
        public ?string $checkInFrom,
        public ?string $checkInTo,
        public ?string $checkOutFrom,
        public ?string $checkOutTo,
        public ?int $guestsCount,
        public ?int $roomsCount,
        public ?array $propertyTypes,
        public ?array $amenities,
        public string $correlationId,
        public ?string $idempotencyKey = null,
    ) {}

    public static function from(Request $request, int $tenantId): self
    {
        return new self(
            tenantId: $tenantId,
            businessGroupId: $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            hotelId: $request->input('hotel_id') ? (int) $request->input('hotel_id') : null,
            // Booking.com/Airbnb фильтры
            minStars: $request->input('min_stars') ? (int) $request->input('min_stars') : null,
            maxStars: $request->input('max_stars') ? (int) $request->input('max_stars') : null,
            minRating: $request->input('min_rating') ? (float) $request->input('min_rating') : null,
            maxRating: $request->input('max_rating') ? (float) $request->input('max_rating') : null,
            minPrice: $request->input('min_price') ? (int) $request->input('min_price') : null,
            maxPrice: $request->input('max_price') ? (int) $request->input('max_price') : null,
            hasWifi: (bool) $request->input('has_wifi', false),
            hasParking: (bool) $request->input('has_parking', false),
            hasPool: (bool) $request->input('has_pool', false),
            hasSpa: (bool) $request->input('has_spa', false),
            hasGym: (bool) $request->input('has_gym', false),
            hasRestaurant: (bool) $request->input('has_restaurant', false),
            hasBreakfastIncluded: (bool) $request->input('has_breakfast_included', false),
            hasKitchen: (bool) $request->input('has_kitchen', false),
            hasAirConditioning: (bool) $request->input('has_air_conditioning', false),
            hasWashingMachine: (bool) $request->input('has_washing_machine', false),
            hasBalcony: (bool) $request->input('has_balcony', false),
            hasSeaView: (bool) $request->input('has_sea_view', false),
            hasMountainView: (bool) $request->input('has_mountain_view', false),
            hasGardenView: (bool) $request->input('has_garden_view', false),
            hasCityView: (bool) $request->input('has_city_view', false),
            petFriendly: (bool) $request->input('pet_friendly', false),
            smokingAllowed: (bool) $request->input('smoking_allowed', false),
            wheelchairAccessible: (bool) $request->input('wheelchair_accessible', false),
            familyFriendly: (bool) $request->input('family_friendly', false),
            adultsOnly: (bool) $request->input('adults_only', false),
            // Специфические фильтры для бассейнов
            poolSizeSqm: $request->input('pool_size_sqm') ? (int) $request->input('pool_size_sqm') : null,
            poolCount: $request->input('pool_count') ? (int) $request->input('pool_count') : null,
            poolHasHeating: (bool) $request->input('pool_has_heating', false),
            poolHasKidsArea: (bool) $request->input('pool_has_kids_area', false),
            poolIndoor: (bool) $request->input('pool_indoor', false),
            poolOutdoor: (bool) $request->input('pool_outdoor', false),
            // Расстояния до объектов (в метрах)
            distanceToSeaMeters: $request->input('distance_to_sea_meters') ? (int) $request->input('distance_to_sea_meters') : null,
            distanceToPharmacyMeters: $request->input('distance_to_pharmacy_meters') ? (int) $request->input('distance_to_pharmacy_meters') : null,
            distanceToGroceryMeters: $request->input('distance_to_grocery_meters') ? (int) $request->input('distance_to_grocery_meters') : null,
            distanceToFloristMeters: $request->input('distance_to_florist_meters') ? (int) $request->input('distance_to_florist_meters') : null,
            distanceToBeachMeters: $request->input('distance_to_beach_meters') ? (int) $request->input('distance_to_beach_meters') : null,
            distanceToBusStopMeters: $request->input('distance_to_bus_stop_meters') ? (int) $request->input('distance_to_bus_stop_meters') : null,
            distanceToTrainStationMeters: $request->input('distance_to_train_station_meters') ? (int) $request->input('distance_to_train_station_meters') : null,
            distanceToAirportMeters: $request->input('distance_to_airport_meters') ? (int) $request->input('distance_to_airport_meters') : null,
            // Дополнительные фильтры
            checkInFrom: $request->input('check_in_from'),
            checkInTo: $request->input('check_in_to'),
            checkOutFrom: $request->input('check_out_from'),
            checkOutTo: $request->input('check_out_to'),
            guestsCount: $request->input('guests_count') ? (int) $request->input('guests_count') : null,
            roomsCount: $request->input('rooms_count') ? (int) $request->input('rooms_count') : null,
            propertyTypes: $request->input('property_types'),
            amenities: $request->input('amenities'),
            correlationId: $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            idempotencyKey: $request->header('Idempotency-Key'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'hotel_id' => $this->hotelId,
            // Booking.com/Airbnb фильтры
            'min_stars' => $this->minStars,
            'max_stars' => $this->maxStars,
            'min_rating' => $this->minRating,
            'max_rating' => $this->maxRating,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'has_wifi' => $this->hasWifi,
            'has_parking' => $this->hasParking,
            'has_pool' => $this->hasPool,
            'has_spa' => $this->hasSpa,
            'has_gym' => $this->hasGym,
            'has_restaurant' => $this->hasRestaurant,
            'has_breakfast_included' => $this->hasBreakfastIncluded,
            'has_kitchen' => $this->hasKitchen,
            'has_air_conditioning' => $this->hasAirConditioning,
            'has_washing_machine' => $this->hasWashingMachine,
            'has_balcony' => $this->hasBalcony,
            'has_sea_view' => $this->hasSeaView,
            'has_mountain_view' => $this->hasMountainView,
            'has_garden_view' => $this->hasGardenView,
            'has_city_view' => $this->hasCityView,
            'pet_friendly' => $this->petFriendly,
            'smoking_allowed' => $this->smokingAllowed,
            'wheelchair_accessible' => $this->wheelchairAccessible,
            'family_friendly' => $this->familyFriendly,
            'adults_only' => $this->adultsOnly,
            // Специфические фильтры для бассейнов
            'pool_size_sqm' => $this->poolSizeSqm,
            'pool_count' => $this->poolCount,
            'pool_has_heating' => $this->poolHasHeating,
            'pool_has_kids_area' => $this->poolHasKidsArea,
            'pool_indoor' => $this->poolIndoor,
            'pool_outdoor' => $this->poolOutdoor,
            // Расстояния до объектов (в метрах)
            'distance_to_sea_meters' => $this->distanceToSeaMeters,
            'distance_to_pharmacy_meters' => $this->distanceToPharmacyMeters,
            'distance_to_grocery_meters' => $this->distanceToGroceryMeters,
            'distance_to_florist_meters' => $this->distanceToFloristMeters,
            'distance_to_beach_meters' => $this->distanceToBeachMeters,
            'distance_to_bus_stop_meters' => $this->distanceToBusStopMeters,
            'distance_to_train_station_meters' => $this->distanceToTrainStationMeters,
            'distance_to_airport_meters' => $this->distanceToAirportMeters,
            // Дополнительные фильтры
            'check_in_from' => $this->checkInFrom,
            'check_in_to' => $this->checkInTo,
            'check_out_from' => $this->checkOutFrom,
            'check_out_to' => $this->checkOutTo,
            'guests_count' => $this->guestsCount,
            'rooms_count' => $this->roomsCount,
            'property_types' => $this->propertyTypes,
            'amenities' => $this->amenities,
            'correlation_id' => $this->correlationId,
        ];
    }
}
