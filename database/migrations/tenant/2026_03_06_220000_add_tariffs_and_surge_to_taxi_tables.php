<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: taxi tariffs and surge handling in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $table->enum('category', ['economy', 'comfort', 'business'])->default('economy')->after('type');
        });

        // Таблица для настроек динамического спроса на основе Глонасс/GPS геозон
        Schema::create('taxi_surge_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('polygon_coords'); // Координаты геозоны спроса
            $table->decimal('multiplier', 5, 2)->default(1.0); // Коэффициент 1.2, 1.5 и т.д.
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Добавление тарификации и данных GPS в поездки
        Schema::table('taxi_trips', function (Blueprint $table) {
            $table->string('tariff_category')->default('economy')->after('status');
            $table->decimal('base_price', 12, 2)->nullable()->after('price');
            $table->decimal('surge_multiplier', 5, 2)->default(1.0)->after('base_price');
            $table->decimal('surge_profit_fleet', 12, 2)->default(0)->after('surge_multiplier'); // 50% прибыли от наценки
            $table->decimal('surge_profit_platform', 12, 2)->default(0)->after('surge_profit_fleet'); // 50% прибыли от наценки
            
            // GPS интеграция
            $table->json('gps_track')->nullable(); // Массив координат [{lat, lon, time, source: 'glonass'}]
            $table->text('current_location')->nullable();
        });

        // Создание тарифов как системных настроек
        Schema::create('taxi_tariffs', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['economy', 'comfort', 'business'])->unique();
            $table->decimal('base_fee', 10, 2); // Посадка
            $table->decimal('per_km_fee', 10, 2); // Цена за км
            $table->decimal('per_minute_fee', 10, 2); // Цена за минуту
            $table->json('category_requirements'); // Требования к машине (год, бренд)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_tariffs');
        Schema::dropIfExists('taxi_surge_zones');
        Schema::table('taxi_cars', function (Blueprint $table) {
            $table->dropColumn('category');
        });
        Schema::table('taxi_trips', function (Blueprint $table) {
            $table->dropColumn(['tariff_category', 'base_price', 'surge_multiplier', 'surge_profit_fleet', 'surge_profit_platform', 'gps_track', 'current_location']);
        });
    }
};
