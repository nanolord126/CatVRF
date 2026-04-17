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
        if (!Schema::hasTable('search_criteria')) {
            Schema::create('search_criteria', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                
                $table->string('vertical')->nullable()->comment('Вертикаль: hotels, beauty, auto, medical и т.д. NULL для публичных');
                $table->string('code')->unique()->comment('Уникальный код критерия');
                $table->string('name')->comment('Название критерия (EN)');
                $table->string('name_ru')->comment('Название критерия (RU)');
                $table->text('description')->nullable()->comment('Описание критерия');
                
                $table->enum('type', ['public', 'vertical_restricted'])->default('public')->comment('Тип: публичный или ограниченный вертикалью');
                $table->enum('data_type', ['boolean', 'integer', 'string', 'decimal', 'json'])->default('string')->comment('Тип данных критерия');
                
                $table->boolean('is_indexed')->default(true)->comment('Индексируется в поиске');
                $table->boolean('is_filterable')->default(true)->comment('Доступен для фильтрации пользователями');
                $table->boolean('is_required')->default(false)->comment('Обязательный критерий');
                
                $table->integer('sort_order')->default(0)->comment('Порядок сортировки');
                $table->json('options')->nullable()->comment('Опции для select/radio (JSON)');
                $table->json('metadata')->nullable()->comment('Метаданные критерия');
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                // Индексы
                $table->index(['tenant_id', 'vertical']);
                $table->index(['type', 'is_indexed']);
                $table->index(['type', 'is_filterable']);
                $table->index('code');
                
                $table->comment('Поисковые критерии для всех вертикалей с разделением на публичные и ограниченные');
            });
        }

        // Заполнение предустановленными критериями
        // Disabled: seeding should be done in database seeders, not migrations
        // $this->seedSearchCriteria();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_criteria');
    }

    /**
     * Заполнение таблицы предустановленными критериями.
     */
    private function seedSearchCriteria(): void
    {
        // Публичные критерии (индексируются для всех вертикалей)
        $publicCriteria = [
            [
                'code' => 'price_min',
                'name' => 'Minimum Price',
                'name_ru' => 'Минимальная цена',
                'description' => 'Минимальная цена товара или услуги',
                'type' => 'public',
                'data_type' => 'decimal',
                'is_indexed' => true,
                'is_filterable' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'price_max',
                'name' => 'Maximum Price',
                'name_ru' => 'Максимальная цена',
                'description' => 'Максимальная цена товара или услуги',
                'type' => 'public',
                'data_type' => 'decimal',
                'is_indexed' => true,
                'is_filterable' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'rating_min',
                'name' => 'Minimum Rating',
                'name_ru' => 'Минимальный рейтинг',
                'description' => 'Минимальный рейтинг (звёзды, баллы)',
                'type' => 'public',
                'data_type' => 'decimal',
                'is_indexed' => true,
                'is_filterable' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'distance',
                'name' => 'Distance',
                'name_ru' => 'Расстояние',
                'description' => 'Расстояние до объекта в метрах',
                'type' => 'public',
                'data_type' => 'integer',
                'is_indexed' => true,
                'is_filterable' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'is_available',
                'name' => 'Available',
                'name_ru' => 'Доступно',
                'description' => 'Товар или услуга доступна для бронирования',
                'type' => 'public',
                'data_type' => 'boolean',
                'is_indexed' => true,
                'is_filterable' => true,
                'sort_order' => 5,
            ],
            [
                'code' => 'is_verified',
                'name' => 'Verified',
                'name_ru' => 'Проверено',
                'description' => 'Поставщик проверен системой',
                'type' => 'public',
                'data_type' => 'boolean',
                'is_indexed' => true,
                'is_filterable' => true,
                'sort_order' => 6,
            ],
        ];

        // Критерии, ограниченные вертикалями (примеры)
        $verticalRestrictedCriteria = [
            // Auto вертикаль
            [
                'vertical' => 'auto',
                'code' => 'brand',
                'name' => 'Car Brand',
                'name_ru' => 'Марка автомобиля',
                'description' => 'Марка автомобиля (BMW, Mercedes, Toyota и т.д.)',
                'type' => 'vertical_restricted',
                'data_type' => 'string',
                'is_indexed' => true,
                'is_filterable' => true,
                'sort_order' => 10,
                'options' => json_encode([
                    'BMW', 'Mercedes', 'Audi', 'Toyota', 'Honda', 'Ford',
                    'Volkswagen', 'Hyundai', 'Kia', 'Nissan', 'Chevrolet',
                ]),
            ],
            [
                'vertical' => 'auto',
                'code' => 'year_min',
                'name' => 'Minimum Year',
                'name_ru' => 'Минимальный год',
                'description' => 'Минимальный год выпуска автомобиля',
                'type' => 'vertical_restricted',
                'data_type' => 'integer',
                'is_indexed' => true,
                'is_filterable' => true,
                'sort_order' => 11,
            ],
            [
                'vertical' => 'auto',
                'code' => 'mileage_max',
                'name' => 'Maximum Mileage',
                'name_ru' => 'Максимальный пробег',
                'description' => 'Максимальный пробег в км',
                'type' => 'vertical_restricted',
                'data_type' => 'integer',
                'is_indexed' => true,
                'is_filterable' => true,
                'sort_order' => 12,
            ],
            // Beauty вертикаль
            [
                'vertical' => 'beauty',
                'code' => 'service_type',
                'name' => 'Service Type',
                'name_ru' => 'Тип услуги',
                'description' => 'Тип косметической услуги',
                'type' => 'vertical_restricted',
                'data_type' => 'string',
                'is_indexed' => true,
                'is_filterable' => true,
                'sort_order' => 10,
                'options' => json_encode([
                    'manicure', 'pedicure', 'haircut', 'coloring', 'styling',
                    'facial', 'massage', 'makeup', 'eyelash', 'eyebrow',
                ]),
            ],
            [
                'vertical' => 'beauty',
                'code' => 'duration_min',
                'name' => 'Duration Min',
                'name_ru' => 'Минимальная длительность',
                'description' => 'Минимальная длительность услуги в минутах',
                'type' => 'vertical_restricted',
                'data_type' => 'integer',
                'is_indexed' => true,
                'is_filterable' => true,
                'sort_order' => 11,
            ],
            // Hotels вертикаль
            [
                'vertical' => 'hotels',
                'code' => 'stars',
                'name' => 'Stars',
                'name_ru' => 'Звёзды',
                'description' => 'Количество звёзд отеля',
                'type' => 'vertical_restricted',
                'data_type' => 'integer',
                'is_indexed' => true,
                'is_filterable' => true,
                'sort_order' => 10,
                'options' => json_encode([1, 2, 3, 4, 5]),
            ],
            [
                'vertical' => 'hotels',
                'code' => 'has_pool',
                'name' => 'Has Pool',
                'name_ru' => 'Бассейн',
                'description' => 'Наличие бассейна',
                'type' => 'vertical_restricted',
                'data_type' => 'boolean',
                'is_indexed' => true,
                'is_filterable' => true,
                'sort_order' => 11,
            ],
            // Medical вертикаль
            [
                'vertical' => 'medical',
                'code' => 'specialty',
                'name' => 'Medical Specialty',
                'name_ru' => 'Специальность',
                'description' => 'Медицинская специальность врача',
                'type' => 'vertical_restricted',
                'data_type' => 'string',
                'is_indexed' => true,
                'is_filterable' => true,
                'sort_order' => 10,
                'options' => json_encode([
                    'general_practitioner', 'cardiologist', 'dermatologist',
                    'pediatrician', 'surgeon', 'neurologist', 'dentist',
                ]),
            ],
            [
                'vertical' => 'medical',
                'code' => 'accepts_insurance',
                'name' => 'Accepts Insurance',
                'name_ru' => 'Принимает страховку',
                'description' => 'Принимает медицинскую страховку',
                'type' => 'vertical_restricted',
                'data_type' => 'boolean',
                'is_indexed' => true,
                'is_filterable' => true,
                'sort_order' => 11,
            ],
        ];

        foreach ($publicCriteria as $criteria) {
            \DB::table('search_criteria')->insert([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'tenant_id' => 1, // Default tenant
                'vertical' => null,
                'code' => $criteria['code'],
                'name' => $criteria['name'],
                'name_ru' => $criteria['name_ru'],
                'description' => $criteria['description'],
                'type' => $criteria['type'],
                'data_type' => $criteria['data_type'],
                'is_indexed' => $criteria['is_indexed'],
                'is_filterable' => $criteria['is_filterable'],
                'is_required' => $criteria['is_required'] ?? false,
                'sort_order' => $criteria['sort_order'],
                'options' => $criteria['options'] ?? null,
                'metadata' => null,
                'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($verticalRestrictedCriteria as $criteria) {
            \DB::table('search_criteria')->insert([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'tenant_id' => 1, // Default tenant
                'vertical' => $criteria['vertical'],
                'code' => $criteria['code'],
                'name' => $criteria['name'],
                'name_ru' => $criteria['name_ru'],
                'description' => $criteria['description'],
                'type' => $criteria['type'],
                'data_type' => $criteria['data_type'],
                'is_indexed' => $criteria['is_indexed'],
                'is_filterable' => $criteria['is_filterable'],
                'is_required' => $criteria['is_required'] ?? false,
                'sort_order' => $criteria['sort_order'],
                'options' => $criteria['options'] ?? null,
                'metadata' => null,
                'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
