<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * РЕЖИМ ЛЮТЫЙ 2026: PET VERTICAL MIGRATIONS
 * 
 * Полная структура для клиник, груминга и зоотоваров.
 * Обязательные поля: tenant_id, uuid, correlation_id, business_group_id, tags (jsonb).
 * Составные индексы для аналитики и фильтрации.
 */
return new class extends Migration
{
    /**
     * Запуск ветеринарной и зоо-инфраструктуры.
     */
    public function up(): void
    {
        // 1. Ветеринарные клиники и зооцентры
        if (!Schema::hasTable('pet_clinics')) {
            Schema::create('pet_clinics', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('business_group_id')->nullable()->index();
                
                $table->string('name')->comment('Название клиники или салона');
                $table->string('type')->default('clinic')->comment('clinic, grooming, shop, hotel');
                $table->string('address');
                $table->jsonb('geo_point')->nullable();
                $table->jsonb('schedule_json')->nullable();
                
                $table->decimal('rating', 3, 2)->default(5.00);
                $table->integer('review_count')->default(0);
                $table->boolean('is_verified')->default(false);
                $table->boolean('has_emergency')->default(false)->comment('Круглосуточная помощь');
                
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Ветеринарные клиники, груминг-салоны и зооцентры');
            });
        }

        // 2. Ветеринары и грумеры
        if (!Schema::hasTable('veterinarians')) {
            Schema::create('veterinarians', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('clinic_id')->constrained('pet_clinics')->onDelete('cascade');
                
                $table->string('full_name');
                $table->jsonb('specialization')->comment('терапевт, хирург, офтальмолог, грумер');
                $table->integer('experience_years')->default(0);
                $table->jsonb('education')->nullable();
                
                $table->decimal('rating', 3, 2)->default(5.00);
                $table->boolean('is_active')->default(true);
                
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                
                $table->comment('Персонал: ветеринарные врачи и специалисты по уходу');
            });
        }

        // 3. Питомец (Пациент)
        if (!Schema::hasTable('pets')) {
            Schema::create('pets', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('owner_id')->index()->comment('User ID владельца');
                
                $table->string('name');
                $table->string('species')->index()->comment('собака, кошка, птица, рептилия');
                $table->string('breed')->nullable()->index();
                $table->date('birth_date')->nullable();
                $table->string('gender')->nullable()->comment('male, female');
                $table->float('weight_kg')->nullable();
                
                $table->boolean('is_neutered')->default(false);
                $table->string('microchip_id')->nullable()->unique();
                $table->jsonb('vaccination_history')->nullable();
                $table->jsonb('allergy_data')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('База данных домашних животных (пациентов)');
            });
        }

        // 4. Ветеринарные и груминг услуги
        if (!Schema::hasTable('pet_services')) {
            Schema::create('pet_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('clinic_id')->constrained('pet_clinics');
                
                $table->string('name');
                $table->string('category')->index()->comment('surgery, grooming, diagnostics, vaccination');
                $table->integer('duration_minutes')->default(30);
                $table->bigInteger('price')->comment('Цена в копейках');
                
                $table->jsonb('consumables_json')->nullable()->comment('Расходники для списания');
                $table->boolean('requires_vaccination')->default(false);
                
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                
                $table->comment('Услуги клиник и салонов красоты для животных');
            });
        }

        // 5. Записи на прием / визиты
        if (!Schema::hasTable('pet_appointments')) {
            Schema::create('pet_appointments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('clinic_id')->index();
                $table->foreignId('pet_id')->constrained('pets');
                $table->foreignId('veterinarian_id')->constrained('veterinarians');
                $table->foreignId('service_id')->constrained('pet_services');
                
                $table->dateTime('starts_at')->index();
                $table->dateTime('ends_at')->index();
                $table->string('status')->default('pending')->index(); // pending, confirmed, completed, cancelled
                
                $table->bigInteger('total_price')->default(0);
                $table->bigInteger('prepayment_amount')->default(0);
                $table->string('payment_status')->default('unpaid');
                
                $table->string('correlation_id')->nullable()->index();
                $table->string('idempotency_key')->nullable()->unique();
                $table->jsonb('metadata')->nullable()->comment('Симптомы, пожелания');
                $table->timestamps();
                
                $table->index(['tenant_id', 'starts_at']);
                $table->comment('Журнал записей на ветеринарный прием и груминг');
            });
        }

        // 6. Зоотовары (Корма, Аксессуары)
        if (!Schema::hasTable('pet_products')) {
            Schema::create('pet_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('clinic_id')->nullable()->comment('Магазин может быть при клинике');
                
                $table->string('name');
                $table->string('sku')->unique();
                $table->string('category')->index(); // food, toy, medicine, accessory
                $table->string('species_restriction')->nullable()->comment('dog, cat, etc');
                
                $table->bigInteger('price')->default(0);
                $table->integer('current_stock')->default(0);
                $table->integer('min_stock_threshold')->default(5);
                
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Каталог зоотоваров и ветеринарных препаратов');
            });
        }

        // 7. Отзывы
        if (!Schema::hasTable('pet_reviews')) {
            Schema::create('pet_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->index();
                $table->morphs('reviewable'); // Ветеринар, Клиника, Продукт
                
                $table->integer('rating')->default(5);
                $table->text('comment');
                $table->boolean('is_verified')->default(false);
                $table->jsonb('photos_json')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Отзывы о ветеринарах, клиниках и товарах');
            });
        }
    }

    /**
     * Откат ветеринарного домена.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_reviews');
        Schema::dropIfExists('pet_products');
        Schema::dropIfExists('pet_appointments');
        Schema::dropIfExists('pet_services');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('veterinarians');
        Schema::dropIfExists('pet_clinics');
    }
};


