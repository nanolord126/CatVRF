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
        if (!Schema::hasTable('property_types')) {
            Schema::create('property_types', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('slug')->unique();
                $table->string('name');
                $table->string('name_ru');
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->integer('min_stars')->nullable();
                $table->integer('max_stars')->nullable();
                $table->json('features')->nullable();
                $table->json('metadata')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['tenant_id', 'is_active']);
                $table->index('slug');
                $table->comment('Типы размещения: отели, санатории, пансионаты, квартиры посуточно, апарты, хостелы');
            });
        }

        // Добавляем property_type_id в таблицу hotels
        if (Schema::hasTable('hotels') && !Schema::hasColumn('hotels', 'property_type_id')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->foreignUuid('property_type_id')
                    ->nullable()
                    ->constrained('property_types')
                    ->onDelete('set null');
                $table->index('property_type_id');
            });
        }

        // Вставляем предустановленные типы размещения
        // Disabled: seeding should be done in database seeders, not migrations
        // $this->seedPropertyTypes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('hotels') && Schema::hasColumn('hotels', 'property_type_id')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->dropForeign(['property_type_id']);
                $table->dropIndex(['property_type_id']);
                $table->dropColumn('property_type_id');
            });
        }

        Schema::dropIfExists('property_types');
    }

    /**
     * Заполнение таблицы предустановленными типами размещения.
     */
    private function seedPropertyTypes(): void
    {
        $types = [
            [
                'slug' => 'hotel',
                'name' => 'Hotel',
                'name_ru' => 'Отель',
                'description' => 'Классический отель с полным спектром услуг',
                'icon' => 'building',
                'sort_order' => 1,
                'min_stars' => 1,
                'max_stars' => 5,
                'features' => ['reception', 'room_service', 'daily_cleaning', 'concierge'],
                'is_active' => true,
            ],
            [
                'slug' => 'sanatorium',
                'name' => 'Sanatorium',
                'name_ru' => 'Санаторий',
                'description' => 'Оздоровительный комплекс с медицинскими процедурами',
                'icon' => 'heart',
                'sort_order' => 2,
                'min_stars' => 3,
                'max_stars' => 5,
                'features' => ['medical_treatment', 'spa', 'dietary_food', 'procedures'],
                'is_active' => true,
            ],
            [
                'slug' => 'boarding_house',
                'name' => 'Boarding House',
                'name_ru' => 'Пансионат',
                'description' => 'Дом отдыха с питанием и базовыми услугами',
                'icon' => 'home',
                'sort_order' => 3,
                'min_stars' => 2,
                'max_stars' => 4,
                'features' => ['meals_included', 'common_areas', 'entertainment'],
                'is_active' => true,
            ],
            [
                'slug' => 'recreation_center',
                'name' => 'Recreation Center',
                'name_ru' => 'Дом отдыха',
                'description' => 'Центр отдыха и развлечений',
                'icon' => 'sun',
                'sort_order' => 4,
                'min_stars' => 2,
                'max_stars' => 4,
                'features' => ['entertainment', 'sports_facilities', 'meals_included'],
                'is_active' => true,
            ],
            [
                'slug' => 'apartment_daily',
                'name' => 'Apartment Daily',
                'name_ru' => 'Квартира посуточно',
                'description' => 'Жилая квартира для краткосрочной аренды',
                'icon' => 'apartment',
                'sort_order' => 5,
                'min_stars' => null,
                'max_stars' => null,
                'features' => ['kitchen', 'washing_machine', 'self_checkin', 'full_apartment'],
                'is_active' => true,
            ],
            [
                'slug' => 'aparthotel',
                'name' => 'Aparthotel',
                'name_ru' => 'Апарт-отель',
                'description' => 'Отель с номерами-студиями и кухнями',
                'icon' => 'building-2',
                'sort_order' => 6,
                'min_stars' => 3,
                'max_stars' => 5,
                'features' => ['kitchen_in_room', 'hotel_services', 'long_stay'],
                'is_active' => true,
            ],
            [
                'slug' => 'hostel',
                'name' => 'Hostel',
                'name_ru' => 'Хостел',
                'description' => 'Бюджетное размещение с общими номерами',
                'icon' => 'users',
                'sort_order' => 7,
                'min_stars' => 1,
                'max_stars' => 2,
                'features' => ['shared_rooms', 'shared_bathroom', 'kitchen_access', 'budget_friendly'],
                'is_active' => true,
            ],
            [
                'slug' => 'guest_house',
                'name' => 'Guest House',
                'name_ru' => 'Гостевой дом',
                'description' => 'Частный дом для гостей с домашней атмосферой',
                'icon' => 'house',
                'sort_order' => 8,
                'min_stars' => 1,
                'max_stars' => 3,
                'features' => ['home_atmosphere', 'personal_attention', 'breakfast_included'],
                'is_active' => true,
            ],
            [
                'slug' => 'villa',
                'name' => 'Villa',
                'name_ru' => 'Вилла',
                'description' => 'Отдельная роскошная вилла с бассейном',
                'icon' => 'home-2',
                'sort_order' => 9,
                'min_stars' => 4,
                'max_stars' => 5,
                'features' => ['private_pool', 'garden', 'full_house', 'luxury'],
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            \DB::table('property_types')->insert([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'tenant_id' => 1, // Default tenant, should be adjusted for multi-tenant
                'slug' => $type['slug'],
                'name' => $type['name'],
                'name_ru' => $type['name_ru'],
                'description' => $type['description'],
                'icon' => $type['icon'],
                'is_active' => $type['is_active'],
                'sort_order' => $type['sort_order'],
                'min_stars' => $type['min_stars'],
                'max_stars' => $type['max_stars'],
                'features' => json_encode($type['features']),
                'metadata' => null,
                'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
