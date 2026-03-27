<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КАНОН 2026: Migration (RealEstate).
 * Создание ядра таблиц для вертикали недвижимости: Объекты, Листинги, Контракты, B2B Сделки.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Core Properties (База объектов: квартиры, дома, коммерция, участки)
        if (!Schema::hasTable('real_estate_properties')) {
            Schema::create('real_estate_properties', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('business_group_id')->nullable()->index();
                
                $table->string('name')->comment('Название объекта');
                $table->text('description')->nullable()->comment('Описание');
                $table->enum('type', ['apartment', 'house', 'land', 'commercial', 'office', 'industrial'])->index();
                $table->string('address')->comment('Полный адрес');
                $table->decimal('lat', 10, 8)->nullable();
                $table->decimal('lon', 11, 8)->nullable();
                
                $table->decimal('area_total', 10, 2)->comment('Общая площадь');
                $table->decimal('area_living', 10, 2)->nullable()->comment('Жилая площадь');
                $table->integer('floor')->nullable();
                $table->integer('floors_total')->nullable();
                $table->integer('rooms')->nullable();
                
                $table->jsonb('amenities')->nullable()->comment('Удобства: лифт, парковка, охрана');
                $table->jsonb('technical_specs')->nullable()->comment('Тех. характеристики: высота потолков, мощность и т.д.');
                $table->jsonb('metadata')->nullable()->comment('Доп. данные');
                $table->jsonb('tags')->nullable()->index();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Ядро объектов недвижимости платформы');
            });
        }

        // 2. Listings (Предложения: Аренда, Продажа, Готовый бизнес)
        if (!Schema::hasTable('real_estate_listings')) {
            Schema::create('real_estate_listings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('property_id')->constrained('real_estate_properties')->onDelete('cascade');
                
                $table->enum('category', ['sale', 'rent_short', 'rent_long', 'business_sale'])->index();
                $table->bigInteger('price_kopecks')->index();
                $table->string('currency', 3)->default('RUB');
                $table->bigInteger('deposit_kopecks')->default(0);
                
                $table->enum('status', ['draft', 'active', 'pending', 'deal', 'archived', 'rejected'])->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                
                $table->boolean('is_b2b')->default(false)->index();
                $table->jsonb('commercial_terms')->nullable()->comment('B2B условия: НДС, каникулы, комиссии');
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Объявления о продаже и аренде недвижимости');
            });
        }

        // 3. Rental Contracts (Контракты на аренду: Бронирования, Договоры)
        if (!Schema::hasTable('real_estate_contracts')) {
            Schema::create('real_estate_contracts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('listing_id')->constrained('real_estate_listings');
                $table->foreignId('user_id')->comment('Арендатор/Покупатель');
                
                $table->date('start_date')->index();
                $table->date('end_date')->nullable()->index();
                
                $table->bigInteger('total_amount_kopecks');
                $table->bigInteger('paid_amount_kopecks')->default(0);
                $table->bigInteger('deposit_amount_kopecks')->default(0);
                
                $table->enum('status', ['draft', 'signed', 'active', 'terminated', 'completed', 'dispute'])->default('draft');
                $table->timestamp('signed_at')->nullable();
                $table->timestamp('check_in_at')->nullable();
                $table->timestamp('check_out_at')->nullable();
                
                $table->jsonb('terms')->nullable()->comment('Условия договора');
                $table->jsonb('payment_schedule')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Контракты и сделки по недвижимости');
            });
        }

        // 4. B2B Deals & Commercial Spaces (Специфика для бизнеса и инвесторов)
        if (!Schema::hasTable('real_estate_b2b_deals')) {
            Schema::create('real_estate_b2b_deals', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('listing_id')->constrained('real_estate_listings');
                
                $table->string('inn', 12)->index();
                $table->string('company_name');
                $table->string('contact_person');
                
                $table->bigInteger('investment_amount_kopecks')->nullable();
                $table->decimal('expected_roi', 5, 2)->nullable();
                
                $table->enum('stage', ['lead', 'negotiation', 'due_diligence', 'contract', 'closed', 'lost'])->default('lead');
                $table->jsonb('deal_data')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('B2B сделки и инвестиционные кейсы');
            });
        }

        // 5. Reviews (Отзывы об объектах и агентах)
        if (!Schema::hasTable('real_estate_reviews')) {
            Schema::create('real_estate_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('property_id')->constrained('real_estate_properties')->onDelete('cascade');
                $table->foreignId('user_id');
                
                $table->integer('rating')->unsigned()->comment('1-5');
                $table->text('comment');
                $table->jsonb('scores')->nullable()->comment('Оценки по параметрам: чистота, локация и т.д.');
                
                $table->boolean('is_verified_purchase')->default(false);
                $table->enum('status', ['pending', 'published', 'rejected'])->default('pending');
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Отзывы на объекты недвижимости');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('real_estate_reviews');
        Schema::dropIfExists('real_estate_b2b_deals');
        Schema::dropIfExists('real_estate_contracts');
        Schema::dropIfExists('real_estate_listings');
        Schema::dropIfExists('real_estate_properties');
    }
};
