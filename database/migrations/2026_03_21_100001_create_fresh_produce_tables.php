<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция: таблицы вертикали FreshProduce (КАНОН 2026).
 * Idempotent — каждая таблица проверяется через hasTable.
 */
return new class extends Migration
{
    public function up(): void
    {
        // -----------------------------------------------------------------
        // farm_suppliers
        // -----------------------------------------------------------------
        if (!Schema::hasTable('farm_suppliers')) {
            Schema::create('farm_suppliers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('contact_name')->nullable();
                $table->string('contact_phone', 32)->nullable();
                $table->string('contact_email', 128)->nullable();
                $table->text('address')->nullable();
                $table->decimal('geo_lat', 10, 7)->nullable();
                $table->decimal('geo_lng', 10, 7)->nullable();
                $table->string('inn', 12)->nullable();
                $table->decimal('commission_rate', 5, 2)->default(14.00);
                $table->boolean('is_verified')->default(false);
                $table->boolean('is_eco_certified')->default(false);
                $table->decimal('rating', 3, 1)->default(0.0);
                $table->unsignedInteger('review_count')->default(0);
                $table->string('status', 32)->default('active')->index();
                $table->jsonb('tags')->nullable();
                $table->jsonb('meta')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->comment('Поставщики/фермеры для вертикали FreshProduce');
            });
        }

        // -----------------------------------------------------------------
        // fresh_products
        // -----------------------------------------------------------------
        if (!Schema::hasTable('fresh_products')) {
            Schema::create('fresh_products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->unsignedBigInteger('farm_supplier_id')->nullable()->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category', 64)->nullable()->index();
                $table->string('unit', 16)->default('kg');
                $table->unsignedInteger('price_per_unit')->default(0)->comment('В копейках');
                $table->unsignedInteger('current_stock')->default(0);
                $table->unsignedInteger('min_stock_threshold')->default(10);
                $table->boolean('is_seasonal')->default(false);
                $table->jsonb('season_months')->nullable();
                $table->boolean('is_eco_certified')->default(false);
                $table->string('eco_certificate_number', 64)->nullable();
                $table->date('harvest_date')->nullable();
                $table->unsignedSmallInteger('expiry_days')->default(7);
                $table->string('status', 32)->default('active')->index();
                $table->jsonb('tags')->nullable();
                $table->jsonb('meta')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'category']);
                $table->comment('Свежие продукты (фрукты, овощи, зелень)');
            });
        }

        // -----------------------------------------------------------------
        // produce_boxes
        // -----------------------------------------------------------------
        if (!Schema::hasTable('produce_boxes')) {
            Schema::create('produce_boxes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->jsonb('contents')->nullable()->comment('Список продуктов в боксе');
                $table->unsignedInteger('price')->default(0)->comment('В копейках');
                $table->unsignedSmallInteger('subscription_days')->default(7);
                $table->decimal('weight_kg', 5, 2)->nullable();
                $table->boolean('is_seasonal')->default(false);
                $table->jsonb('season_months')->nullable();
                $table->string('photo_url')->nullable();
                $table->string('status', 32)->default('active')->index();
                $table->jsonb('tags')->nullable();
                $table->jsonb('meta')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->comment('Боксы свежих продуктов для доставки/подписки');
            });
        }

        // -----------------------------------------------------------------
        // produce_subscriptions
        // -----------------------------------------------------------------
        if (!Schema::hasTable('produce_subscriptions')) {
            Schema::create('produce_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('box_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('frequency', 32)->default('weekly');
                $table->text('delivery_address');
                $table->decimal('delivery_lat', 10, 7)->nullable();
                $table->decimal('delivery_lng', 10, 7)->nullable();
                $table->string('preferred_slot', 32)->nullable();
                $table->date('next_delivery_date')->nullable()->index();
                $table->unsignedInteger('total_deliveries')->default(0);
                $table->unsignedInteger('price_per_box')->default(0)->comment('В копейках');
                $table->string('status', 32)->default('active')->index();
                $table->date('paused_until')->nullable();
                $table->jsonb('tags')->nullable();
                $table->jsonb('meta')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index(['client_id', 'status']);
                $table->comment('Подписки на еженедельные/ежемесячные боксы');
            });
        }

        // -----------------------------------------------------------------
        // produce_orders
        // -----------------------------------------------------------------
        if (!Schema::hasTable('produce_orders')) {
            Schema::create('produce_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('farm_supplier_id')->nullable()->index();
                $table->unsignedBigInteger('subscription_id')->nullable()->index();
                $table->unsignedBigInteger('courier_id')->nullable()->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('idempotency_key', 64)->nullable()->unique()->index();
                $table->jsonb('items')->nullable()->comment('Состав заказа');
                $table->unsignedInteger('total_amount')->default(0)->comment('В копейках');
                $table->text('delivery_address');
                $table->decimal('delivery_lat', 10, 7)->nullable();
                $table->decimal('delivery_lng', 10, 7)->nullable();
                $table->date('delivery_date')->nullable()->index();
                $table->string('delivery_slot', 32)->nullable();
                $table->string('status', 32)->default('pending')->index();
                $table->string('payment_status', 32)->default('awaiting')->index();
                $table->unsignedBigInteger('payment_transaction_id')->nullable()->index();
                $table->string('quality_photo_url')->nullable();
                $table->timestamp('quality_checked_at')->nullable();
                $table->timestamp('packed_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->jsonb('tags')->nullable();
                $table->jsonb('meta')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'delivery_date']);
                $table->index(['client_id', 'status']);
                $table->comment('Заказы свежих продуктов (разовые и по подписке)');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('produce_orders');
        Schema::dropIfExists('produce_subscriptions');
        Schema::dropIfExists('produce_boxes');
        Schema::dropIfExists('fresh_products');
        Schema::dropIfExists('farm_suppliers');
    }
};
