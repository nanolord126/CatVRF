<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Hotel — модель отеля CatVRF 2026 (основная).
 *
 * Содержит данные об отеле: название, адрес, звёзды, рейтинг,
 * расписание, номера и удобства. Tenant-scoped.
 *
 * @package CatVRF
 * @version 2026.1
 * @see https://catvrf.ru/docs/hotel
 */
final class Hotel extends Model
{

    protected $table = 'hotels';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'property_type_id',
        'name',
        'description',
        'address',
        'geo_point',
        'stars',
        'is_active',
        'schedule_json',
        'rating',
        'review_count',
        'correlation_id',
        'tags',
        // Booking.com/Airbnb фильтры - в карточке объекта
        'min_price_per_night',
        'max_price_per_night',
        'has_wifi',
        'has_parking',
        'has_pool',
        'has_spa',
        'has_gym',
        'has_restaurant',
        'has_breakfast_included',
        'has_kitchen',
        'has_air_conditioning',
        'has_washing_machine',
        'has_balcony',
        'has_elevator',
        'has_24h_reception',
        'has_concierge',
        'has_sea_view',
        'has_mountain_view',
        'has_garden_view',
        'has_city_view',
        'has_pool_view',
        'has_lake_view',
        'pet_friendly',
        'smoking_allowed',
        'wheelchair_accessible',
        'family_friendly',
        'adults_only',
        'all_inclusive',
        // Специфические фильтры для бассейнов
        'pool_size_sqm',
        'pool_count',
        'pool_has_heating',
        'pool_has_kids_area',
        'pool_indoor',
        'pool_outdoor',
        'pool_has_slides',
        'pool_has_bar',
        // Расстояния до объектов (в метрах)
        'distance_to_sea_meters',
        'distance_to_beach_meters',
        'distance_to_pharmacy_meters',
        'distance_to_grocery_meters',
        'distance_to_florist_meters',
        'distance_to_bus_stop_meters',
        'distance_to_train_station_meters',
        'distance_to_airport_meters',
        'distance_to_city_center_meters',
        'distance_to_ski_lift_meters',
        // Геолокация для поиска по радиусу
        'latitude',
        'longitude',
        'search_radius_meters',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'schedule_json' => 'json',
        'tags' => 'json',
        'stars' => 'integer',
        'rating' => 'float',
        'review_count' => 'integer',
        // Booking.com/Airbnb фильтры - boolean
        'has_wifi' => 'boolean',
        'has_parking' => 'boolean',
        'has_pool' => 'boolean',
        'has_spa' => 'boolean',
        'has_gym' => 'boolean',
        'has_restaurant' => 'boolean',
        'has_breakfast_included' => 'boolean',
        'has_kitchen' => 'boolean',
        'has_air_conditioning' => 'boolean',
        'has_washing_machine' => 'boolean',
        'has_balcony' => 'boolean',
        'has_elevator' => 'boolean',
        'has_24h_reception' => 'boolean',
        'has_concierge' => 'boolean',
        'has_sea_view' => 'boolean',
        'has_mountain_view' => 'boolean',
        'has_garden_view' => 'boolean',
        'has_city_view' => 'boolean',
        'has_pool_view' => 'boolean',
        'has_lake_view' => 'boolean',
        'pet_friendly' => 'boolean',
        'smoking_allowed' => 'boolean',
        'wheelchair_accessible' => 'boolean',
        'family_friendly' => 'boolean',
        'adults_only' => 'boolean',
        'all_inclusive' => 'boolean',
        // Специфические фильтры для бассейнов
        'pool_size_sqm' => 'integer',
        'pool_count' => 'integer',
        'pool_has_heating' => 'boolean',
        'pool_has_kids_area' => 'boolean',
        'pool_indoor' => 'boolean',
        'pool_outdoor' => 'boolean',
        'pool_has_slides' => 'boolean',
        'pool_has_bar' => 'boolean',
        // Расстояния до объектов (в метрах)
        'distance_to_sea_meters' => 'integer',
        'distance_to_beach_meters' => 'integer',
        'distance_to_pharmacy_meters' => 'integer',
        'distance_to_grocery_meters' => 'integer',
        'distance_to_florist_meters' => 'integer',
        'distance_to_bus_stop_meters' => 'integer',
        'distance_to_train_station_meters' => 'integer',
        'distance_to_airport_meters' => 'integer',
        'distance_to_city_center_meters' => 'integer',
        'distance_to_ski_lift_meters' => 'integer',
        // Геолокация
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'search_radius_meters' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });

        static::addGlobalScope('tenant', function ($builder): void {
            $builder->where('hotels.tenant_id', tenant()->id);
        });
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'hotel_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'hotel_id');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(
            Amenity::class,
            'hotel_amenity_pivot',
            'hotel_id',
            'amenity_id',
        );
    }

    public function b2bContracts(): HasMany
    {
        return $this->hasMany(B2BContract::class, 'hotel_id');
    }

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    public function searchFilter(): HasOne
    {
        return $this->hasOne(HotelSearchFilter::class, 'hotel_id');
    }

    /**
     * Получить строковое представление модели.
     */
    public function __toString(): string
    {
        return sprintf(
            '%s[id=%s, name=%s, stars=%d]',
            static::class,
            $this->id ?? 'new',
            $this->name ?? '',
            $this->stars ?? 0,
        );
    }
}
