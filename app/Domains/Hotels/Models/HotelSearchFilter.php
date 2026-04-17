<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * HotelSearchFilter — поисковые фильтры для Hotels CatVRF 2026.
 *
 * Хранит фильтры для поиска, аналогичные Booking.com и Airbnb,
 * плюс специфические фильтры для бассейнов и расстояний до объектов.
 *
 * Booking.com/Airbnb фильтры:
 * - Звёздность, рейтинг, цена, удобства, питание, тип размещения
 *
 * Специфические фильтры:
 * - Размер бассейна, число бассейнов, подогрев, детский бассейн
 * - Расстояния до моря, аптеки, продуктового, цветочного (в метрах)
 *
 * @package CatVRF
 * @version 2026.1
 */
final class HotelSearchFilter extends Model
{
    protected $table = 'hotel_search_filters';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'hotel_id',
        // Booking.com/Airbnb фильтры
        'min_stars',
        'max_stars',
        'min_rating',
        'max_rating',
        'min_price',
        'max_price',
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
        'has_sea_view',
        'has_mountain_view',
        'has_garden_view',
        'has_city_view',
        'pet_friendly',
        'smoking_allowed',
        'wheelchair_accessible',
        'family_friendly',
        'adults_only',
        // Специфические фильтры для бассейнов
        'pool_size_sqm',
        'pool_count',
        'pool_has_heating',
        'pool_has_kids_area',
        'pool_indoor',
        'pool_outdoor',
        // Расстояния до объектов (в метрах)
        'distance_to_sea_meters',
        'distance_to_pharmacy_meters',
        'distance_to_grocery_meters',
        'distance_to_florist_meters',
        'distance_to_beach_meters',
        'distance_to_bus_stop_meters',
        'distance_to_train_station_meters',
        'distance_to_airport_meters',
        // Дополнительные фильтры
        'check_in_from',
        'check_in_to',
        'check_out_from',
        'check_out_to',
        'guests_count',
        'rooms_count',
        'property_types', // JSON array of property type slugs
        'amenities', // JSON array of amenity IDs
        'correlation_id',
    ];

    protected $casts = [
        'min_stars' => 'integer',
        'max_stars' => 'integer',
        'min_rating' => 'float',
        'max_rating' => 'float',
        'min_price' => 'integer',
        'max_price' => 'integer',
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
        'has_sea_view' => 'boolean',
        'has_mountain_view' => 'boolean',
        'has_garden_view' => 'boolean',
        'has_city_view' => 'boolean',
        'pet_friendly' => 'boolean',
        'smoking_allowed' => 'boolean',
        'wheelchair_accessible' => 'boolean',
        'family_friendly' => 'boolean',
        'adults_only' => 'boolean',
        'pool_size_sqm' => 'integer',
        'pool_count' => 'integer',
        'pool_has_heating' => 'boolean',
        'pool_has_kids_area' => 'boolean',
        'pool_indoor' => 'boolean',
        'pool_outdoor' => 'boolean',
        'distance_to_sea_meters' => 'integer',
        'distance_to_pharmacy_meters' => 'integer',
        'distance_to_grocery_meters' => 'integer',
        'distance_to_florist_meters' => 'integer',
        'distance_to_beach_meters' => 'integer',
        'distance_to_bus_stop_meters' => 'integer',
        'distance_to_train_station_meters' => 'integer',
        'distance_to_airport_meters' => 'integer',
        'guests_count' => 'integer',
        'rooms_count' => 'integer',
        'property_types' => 'json',
        'amenities' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });

        static::addGlobalScope('tenant', function ($builder): void {
            $builder->where('hotel_search_filters.tenant_id', tenant()->id);
        });
    }

    /**
     * Отели, к которым применяются фильтры.
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    /**
     * Получить строковое представление модели.
     */
    public function __toString(): string
    {
        return sprintf(
            '%s[id=%s, hotel_id=%s, stars=%d-%d]',
            static::class,
            $this->id ?? 'new',
            $this->hotel_id ?? 'N/A',
            $this->min_stars ?? 0,
            $this->max_stars ?? 5,
        );
    }
}
