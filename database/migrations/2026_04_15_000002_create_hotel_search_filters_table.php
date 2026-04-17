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
        if (!Schema::hasTable('hotel_search_filters')) {
            Schema::create('hotel_search_filters', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignUuid('hotel_id')->nullable()->constrained('hotels')->onDelete('cascade');
                
                // Booking.com/Airbnb фильтры
                $table->integer('min_stars')->nullable();
                $table->integer('max_stars')->nullable();
                $table->decimal('min_rating', 3, 1)->nullable();
                $table->decimal('max_rating', 3, 1)->nullable();
                $table->integer('min_price')->nullable();
                $table->integer('max_price')->nullable();
                
                // Удобства (boolean)
                $table->boolean('has_wifi')->default(false);
                $table->boolean('has_parking')->default(false);
                $table->boolean('has_pool')->default(false);
                $table->boolean('has_spa')->default(false);
                $table->boolean('has_gym')->default(false);
                $table->boolean('has_restaurant')->default(false);
                $table->boolean('has_breakfast_included')->default(false);
                $table->boolean('has_kitchen')->default(false);
                $table->boolean('has_air_conditioning')->default(false);
                $table->boolean('has_washing_machine')->default(false);
                $table->boolean('has_balcony')->default(false);
                
                // Вид из окна
                $table->boolean('has_sea_view')->default(false);
                $table->boolean('has_mountain_view')->default(false);
                $table->boolean('has_garden_view')->default(false);
                $table->boolean('has_city_view')->default(false);
                
                // Политика
                $table->boolean('pet_friendly')->default(false);
                $table->boolean('smoking_allowed')->default(false);
                $table->boolean('wheelchair_accessible')->default(false);
                $table->boolean('family_friendly')->default(false);
                $table->boolean('adults_only')->default(false);
                
                // Специфические фильтры для бассейнов
                $table->integer('pool_size_sqm')->nullable()->comment('Размер бассейна в м²');
                $table->integer('pool_count')->nullable()->comment('Число бассейнов');
                $table->boolean('pool_has_heating')->default(false)->comment('Подогрев бассейна');
                $table->boolean('pool_has_kids_area')->default(false)->comment('Детский бассейн');
                $table->boolean('pool_indoor')->default(false)->comment('Крытый бассейн');
                $table->boolean('pool_outdoor')->default(false)->comment('Открытый бассейн');
                
                // Расстояния до объектов (в метрах)
                $table->integer('distance_to_sea_meters')->nullable()->comment('Расстояние до моря в метрах');
                $table->integer('distance_to_pharmacy_meters')->nullable()->comment('Расстояние до аптеки в метрах');
                $table->integer('distance_to_grocery_meters')->nullable()->comment('Расстояние до продуктового в метрах');
                $table->integer('distance_to_florist_meters')->nullable()->comment('Расстояние до цветочного в метрах');
                $table->integer('distance_to_beach_meters')->nullable()->comment('Расстояние до пляжа в метрах');
                $table->integer('distance_to_bus_stop_meters')->nullable()->comment('Расстояние до автобусной остановки в метрах');
                $table->integer('distance_to_train_station_meters')->nullable()->comment('Расстояние до ж/д станции в метрах');
                $table->integer('distance_to_airport_meters')->nullable()->comment('Расстояние до аэропорта в метрах');
                
                // Дополнительные фильтры
                $table->time('check_in_from')->nullable();
                $table->time('check_in_to')->nullable();
                $table->time('check_out_from')->nullable();
                $table->time('check_out_to')->nullable();
                $table->integer('guests_count')->nullable();
                $table->integer('rooms_count')->nullable();
                
                // JSON поля
                $table->json('property_types')->nullable()->comment('Массив slug типов размещения');
                $table->json('amenities')->nullable()->comment('Массив ID удобств');
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                // Индексы для быстрого поиска
                $table->index(['tenant_id', 'hotel_id']);
                $table->index(['min_stars', 'max_stars']);
                $table->index(['min_rating', 'max_rating']);
                $table->index(['min_price', 'max_price']);
                $table->index('has_pool');
                $table->index('has_sea_view');
                $table->index('distance_to_sea_meters');
                $table->index('distance_to_beach_meters');
                
                $table->comment('Поисковые фильтры для Hotels (Booking.com/Airbnb + специфические)');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_search_filters');
    }
};
