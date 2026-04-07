<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ritual Services Domain Migrations — Production Ready 2026
 * 
 * Включает: RitualAgency, MemorialProduct, FuneralOrder, RitualPreOrder, MemorialCertificate.
 * Реализовано для B2B (партнерские услуги) и B2C (граждане).
 * Особенности: Строгая конфиденциальность, поддержка рассрочки, интеграция с Wallet.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Ритуальные агентства и бюро
        if (!Schema::hasTable('ritual_agencies')) {
            Schema::create('ritual_agencies', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name')->comment('Название агентства');
                $table->string('license_number')->nullable()->comment('Лицензия на ритуальную деятельность');
                $table->string('address')->comment('Юридический адрес');
                $table->jsonb('contact_info')->comment('Телефоны, почта, контактные лица');
                $table->float('rating')->default(5.00);
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'is_active']);
                $table->comment('Таблица ритуальных агентств и бюро услуг');
            });
        }

        // 2. Мемориальные товары (Гробы, памятники, венки)
        if (!Schema::hasTable('ritual_memorial_products')) {
            Schema::create('ritual_memorial_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('agency_id')->constrained('ritual_agencies')->onDelete('cascade');
                $table->string('name')->comment('Наименование изделия');
                $table->text('description')->nullable();
                $table->string('category')->comment('Тип: гроб, памятник, урна, аксессуар');
                $table->string('material')->nullable();
                $table->bigInteger('price_kopecks')->unsigned()->comment('Базовая стоимость');
                $table->integer('stock_count')->default(0);
                $table->jsonb('customization_options')->nullable()->comment('Опции гравировки, цвета и т.д.');
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();

                $table->index(['agency_id', 'category']);
                $table->comment('Мемориальные товары и изделия');
            });
        }

        // 3. Заказы на организацию похорон (Оперативные услуги)
        if (!Schema::hasTable('ritual_funeral_orders')) {
            Schema::create('ritual_funeral_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('agency_id')->constrained('ritual_agencies')->onDelete('cascade');
                $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
                $table->string('deceased_name')->nullable()->comment('ФИО усопшего (зашифровано или конфиденциально)');
                $table->date('death_date')->nullable();
                $table->datetime('funeral_date')->nullable();
                $table->string('burial_location')->nullable()->comment('Кладбище / Крематорий');
                $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending');
                $table->bigInteger('total_amount_kopecks')->unsigned();
                $table->bigInteger('paid_amount_kopecks')->unsigned()->default(0);
                $table->jsonb('selected_services')->comment('Список выбранных услуг (пакеты)');
                $table->boolean('is_installment')->default(false)->comment('Флаг рассрочки');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Заказы на комплексную организацию похорон');
            });
        }

        // 4. Предварительные договора (Прижизненные договора)
        if (!Schema::hasTable('ritual_pre_orders')) {
            Schema::create('ritual_pre_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
                $table->string('contract_number')->unique();
                $table->bigInteger('target_amount_kopecks')->unsigned();
                $table->bigInteger('accumulated_amount_kopecks')->unsigned()->default(0);
                $table->enum('status', ['active', 'closed', 'terminated'])->default('active');
                $table->jsonb('wishes_json')->nullable()->comment('Особые пожелания заказчика');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Прижизненные договора на ритуальные услуги');
            });
        }

        // 5. Мемориальные сертификаты (Подарочные и благотворительные)
        if (!Schema::hasTable('ritual_memorial_certificates')) {
            Schema::create('ritual_memorial_certificates', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('code')->unique();
                $table->bigInteger('balance_kopecks')->unsigned();
                $table->datetime('expires_at')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Мемориальные сертификаты и ваучеры');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ritual_memorial_certificates');
        Schema::dropIfExists('ritual_pre_orders');
        Schema::dropIfExists('ritual_funeral_orders');
        Schema::dropIfExists('ritual_memorial_products');
        Schema::dropIfExists('ritual_agencies');
    }
};


