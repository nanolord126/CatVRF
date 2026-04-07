<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('properties')) {
            Schema::create('properties', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('business_group_id')->nullable()->index();
                $table->string('correlation_id')->nullable()->index();
                
                $table->string('name')->comment('Название объекта');
                $table->string('address')->comment('Полный адрес');
                $table->decimal('lat', 10, 8)->nullable();
                $table->decimal('lon', 11, 8)->nullable();
                $table->enum('type', ['apartment', 'house', 'land', 'commercial', 'office', 'ready_business'])->index();
                $table->decimal('area', 12, 2)->comment('Площадь');
                $table->integer('rooms')->nullable();
                $table->integer('floor')->nullable();
                $table->jsonb('features')->default('[]')->comment('Характеристики (бассейн, паркинг и т.д.)');
                $table->string('status')->default('available')->index();
                
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Объекты недвижимости (RealEstate)');
            });
        }

        if (!Schema::hasTable('real_estate_listings')) {
            Schema::create('real_estate_listings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
                $table->string('correlation_id')->nullable()->index();
                
                $table->enum('deal_type', ['sale', 'rent_long', 'rent_short'])->index();
                $table->bigInteger('price')->comment('Цена в копейках');
                $table->bigInteger('deposit')->default(0)->comment('Залог/депозит');
                $table->integer('commission_percent')->default(14)->comment('Комиссия платформы');
                $table->boolean('is_b2b')->default(false)->index()->comment('Для инвесторов/бизнеса');
                $table->jsonb('rules')->default('[]')->comment('Правила (чекин/чекаут, запреты)');
                
                $table->string('status')->default('active')->index();
                $table->timestamp('published_at')->nullable();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Объявления о недвижимости');
            });
        }

        if (!Schema::hasTable('rental_contracts')) {
            Schema::create('rental_contracts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('listing_id')->constrained('real_estate_listings');
                $table->foreignId('tenant_user_id')->comment('Кто арендует');
                $table->string('correlation_id')->nullable()->index();
                
                $table->timestamp('start_date');
                $table->timestamp('end_date')->nullable();
                $table->bigInteger('monthly_rent')->comment('Аренда в коп.');
                $table->bigInteger('paid_deposit')->comment('Уплаченный залог');
                $table->string('contract_status')->default('pending')->index();
                $table->jsonb('terms')->default('[]')->comment('Условия договора');
                
                $table->timestamps();
                $table->comment('Договоры аренды');
            });
        }

        if (!Schema::hasTable('b2b_deals')) {
            Schema::create('b2b_deals', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('listing_id')->constrained('real_estate_listings');
                $table->foreignId('investor_id')->comment('ID инвестора (business_group)');
                $table->string('correlation_id')->nullable()->index();
                
                $table->bigInteger('deal_amount')->comment('Сумма сделки в коп.');
                $table->decimal('expected_roi', 5, 2)->nullable()->comment('Ожидаемая доходность');
                $table->string('status')->default('negotiation')->index();
                $table->jsonb('deal_structure')->nullable()->comment('Структура сделки');
                
                $table->timestamps();
                $table->comment('Крупные B2B сделки и инвестиции');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_deals');
        Schema::dropIfExists('rental_contracts');
        Schema::dropIfExists('real_estate_listings');
        Schema::dropIfExists('properties');
    }
};


