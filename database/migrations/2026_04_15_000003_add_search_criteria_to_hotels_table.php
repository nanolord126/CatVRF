<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('hotels')) {
            Schema::table('hotels', function (Blueprint $table) {
                // Booking.com/Airbnb фильтры - добавляем прямо в карточку объекта
                $table->integer('min_price_per_night')->nullable()->after('review_count')->comment('Минимальная цена за ночь');
                $table->integer('max_price_per_night')->nullable()->after('min_price_per_night')->comment('Максимальная цена за ночь');
                
                // Удобства (boolean) - для быстрого фильтрования
                $table->boolean('has_wifi')->default(false)->after('max_price_per_night');
                $table->boolean('has_parking')->default(false)->after('has_wifi');
                $table->boolean('has_pool')->default(false)->after('has_parking');
                $table->boolean('has_spa')->default(false)->after('has_pool');
                $table->boolean('has_gym')->default(false)->after('has_spa');
                $table->boolean('has_restaurant')->default(false)->after('has_gym');
                $table->boolean('has_breakfast_included')->default(false)->after('has_restaurant');
                $table->boolean('has_kitchen')->default(false)->after('has_breakfast_included');
                $table->boolean('has_air_conditioning')->default(false)->after('has_kitchen');
                $table->boolean('has_washing_machine')->default(false)->after('has_air_conditioning');
                $table->boolean('has_balcony')->default(false)->after('has_washing_machine');
                $table->boolean('has_elevator')->default(false)->after('has_balcony');
                $table->boolean('has_24h_reception')->default(false)->after('has_elevator');
                $table->boolean('has_concierge')->default(false)->after('has_24h_reception');
                
                // Вид из окна
                $table->boolean('has_sea_view')->default(false)->after('has_concierge');
                $table->boolean('has_mountain_view')->default(false)->after('has_sea_view');
                $table->boolean('has_garden_view')->default(false)->after('has_mountain_view');
                $table->boolean('has_city_view')->default(false)->after('has_garden_view');
                $table->boolean('has_pool_view')->default(false)->after('has_city_view');
                $table->boolean('has_lake_view')->default(false)->after('has_pool_view');
                
                // Политика
                $table->boolean('pet_friendly')->default(false)->after('has_lake_view');
                $table->boolean('smoking_allowed')->default(false)->after('pet_friendly');
                $table->boolean('wheelchair_accessible')->default(false)->after('smoking_allowed');
                $table->boolean('family_friendly')->default(false)->after('wheelchair_accessible');
                $table->boolean('adults_only')->default(false)->after('family_friendly');
                $table->boolean('all_inclusive')->default(false)->after('adults_only');
                
                // Специфические фильтры для бассейнов
                $table->integer('pool_size_sqm')->nullable()->after('all_inclusive')->comment('Размер бассейна в м²');
                $table->integer('pool_count')->nullable()->after('pool_size_sqm')->comment('Число бассейнов');
                $table->boolean('pool_has_heating')->default(false)->after('pool_count')->comment('Подогрев бассейна');
                $table->boolean('pool_has_kids_area')->default(false)->after('pool_has_heating')->comment('Детский бассейн');
                $table->boolean('pool_indoor')->default(false)->after('pool_has_kids_area')->comment('Крытый бассейн');
                $table->boolean('pool_outdoor')->default(false)->after('pool_indoor')->comment('Открытый бассейн');
                $table->boolean('pool_has_slides')->default(false)->after('pool_outdoor')->comment('Горки в бассейне');
                $table->boolean('pool_has_bar')->default(false)->after('pool_has_slides')->comment('Бар у бассейна');
                
                // Расстояния до объектов (в метрах)
                $table->integer('distance_to_sea_meters')->nullable()->after('pool_has_bar')->comment('Расстояние до моря в метрах');
                $table->integer('distance_to_beach_meters')->nullable()->after('distance_to_sea_meters')->comment('Расстояние до пляжа в метрах');
                $table->integer('distance_to_pharmacy_meters')->nullable()->after('distance_to_beach_meters')->comment('Расстояние до аптеки в метрах');
                $table->integer('distance_to_grocery_meters')->nullable()->after('distance_to_pharmacy_meters')->comment('Расстояние до продуктового в метрах');
                $table->integer('distance_to_florist_meters')->nullable()->after('distance_to_grocery_meters')->comment('Расстояние до цветочного в метрах');
                $table->integer('distance_to_bus_stop_meters')->nullable()->after('distance_to_florist_meters')->comment('Расстояние до автобусной остановки в метрах');
                $table->integer('distance_to_train_station_meters')->nullable()->after('distance_to_bus_stop_meters')->comment('Расстояние до ж/д станции в метрах');
                $table->integer('distance_to_airport_meters')->nullable()->after('distance_to_train_station_meters')->comment('Расстояние до аэропорта в метрах');
                $table->integer('distance_to_city_center_meters')->nullable()->after('distance_to_airport_meters')->comment('Расстояние до центра города в метрах');
                $table->integer('distance_to_ski_lift_meters')->nullable()->after('distance_to_city_center_meters')->comment('Расстояние до подъемника в метрах');
                
                // Геолокация для поиска по радиусу
                $table->decimal('latitude', 10, 8)->nullable()->after('distance_to_ski_lift_meters')->comment('Широта');
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude')->comment('Долгота');
                $table->integer('search_radius_meters')->nullable()->after('longitude')->comment('Радиус поиска в метрах');
                
                // Индексы для быстрого поиска
                $table->index(['min_price_per_night', 'max_price_per_night']);
                $table->index('has_pool');
                $table->index('has_sea_view');
                $table->index('has_spa');
                $table->index('pet_friendly');
                $table->index('family_friendly');
                $table->index('distance_to_sea_meters');
                $table->index('distance_to_beach_meters');
                $table->index('distance_to_city_center_meters');
                $table->index(['latitude', 'longitude']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('hotels')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->dropIndex(['latitude', 'longitude']);
                $table->dropIndex('distance_to_city_center_meters');
                $table->dropIndex('distance_to_beach_meters');
                $table->dropIndex('distance_to_sea_meters');
                $table->dropIndex('family_friendly');
                $table->dropIndex('pet_friendly');
                $table->dropIndex('has_spa');
                $table->dropIndex('has_sea_view');
                $table->dropIndex('has_pool');
                $table->dropIndex(['min_price_per_night', 'max_price_per_night']);
                
                $table->dropColumn([
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
                    'pool_size_sqm',
                    'pool_count',
                    'pool_has_heating',
                    'pool_has_kids_area',
                    'pool_indoor',
                    'pool_outdoor',
                    'pool_has_slides',
                    'pool_has_bar',
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
                    'latitude',
                    'longitude',
                    'search_radius_meters',
                ]);
            });
        }
    }
};
