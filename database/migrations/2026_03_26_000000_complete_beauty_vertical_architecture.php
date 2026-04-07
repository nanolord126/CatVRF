<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КАНОН 2026: Идемпотентная миграция вертикали Beauty (Layer 1)
 * Все таблицы с комментариями, correlation_id, uuid и tenant scoping.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Салоны красоты
        if (!Schema::hasTable('beauty_salons')) {
            Schema::create('beauty_salons', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
                $table->string('name')->index();
                $table->text('address');
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('description')->nullable();
                $table->json('working_hours')->nullable();
                $table->jsonb('geo_point')->nullable()->comment('Geo coordinates {lat, lon}');
                $table->float('rating')->default(0)->index();
                $table->integer('review_count')->default(0);
                $table->boolean('is_verified')->default(false)->index();
                $table->jsonb('tags')->nullable()->comment('Analytics tags');
                $table->jsonb('metadata')->nullable()->comment('Additional vendor-specific data');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'is_verified']);
                $table->comment('Beauty salons main table (Domain: Beauty)');
            });
        }

        // 2. Мастера
        if (!Schema::hasTable('masters')) {
            Schema::create('masters', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('salon_id')->nullable()->constrained('beauty_salons')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('full_name')->index();
                $table->jsonb('specialization')->nullable()->comment('Master specializations (nails, hair, etc.)');
                $table->integer('experience_years')->default(0);
                $table->float('rating')->default(0)->index();
                $table->integer('review_count')->default(0);
                $table->text('bio')->nullable();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['salon_id', 'rating']);
                $table->comment('Beauty masters/stylists (Domain: Beauty)');
            });
        }

        // 3. Услуги
        if (!Schema::hasTable('beauty_services')) {
            Schema::create('beauty_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('salon_id')->nullable()->constrained('beauty_salons')->onDelete('cascade');
                $table->foreignId('master_id')->nullable()->constrained('masters')->onDelete('cascade');
                $table->string('name')->index();
                $table->text('description')->nullable();
                $table->integer('duration_minutes')->default(30);
                $table->bigInteger('price')->comment('Price in kopeks (int)')->index();
                $table->jsonb('consumables')->nullable()->comment('Required consumables list with quantities');
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['salon_id', 'price']);
                $table->comment('Beauty services catalog (Domain: Beauty)');
            });
        }

        // 4. Записи (Appointments)
        if (!Schema::hasTable('beauty_appointments')) {
            Schema::create('beauty_appointments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('salon_id')->constrained('beauty_salons')->onDelete('cascade');
                $table->foreignId('master_id')->constrained('masters')->onDelete('cascade');
                $table->foreignId('service_id')->constrained('beauty_services')->onDelete('cascade');
                $table->dateTime('datetime_start')->index();
                $table->dateTime('datetime_end')->index();
                $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])->default('pending')->index();
                $table->bigInteger('price')->comment('Final price in kopeks');
                $table->enum('payment_status', ['pending', 'authorized', 'captured', 'refunded', 'failed'])->default('pending')->index();
                $table->text('client_comment')->nullable();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['master_id', 'datetime_start']);
                $table->index(['user_id', 'status']);
                $table->comment('Main appointment table for Beauty domain');
            });
        }

        // 5. Расходные материалы (Consumables)
        if (!Schema::hasTable('beauty_consumables')) {
            Schema::create('beauty_consumables', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('salon_id')->constrained('beauty_salons')->onDelete('cascade');
                $table->string('name')->index();
                $table->string('unit')->default('pcs')->comment('Unit of measurement (ml, pcs, g)');
                $table->integer('current_stock')->default(0);
                $table->integer('min_threshold')->default(10);
                $table->bigInteger('unit_cost')->default(0)->comment('Cost in kopeks');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Inventory of consumables for beauty salons');
            });
        }

        // 6. Товары (Beauty Products)
        if (!Schema::hasTable('beauty_products')) {
            Schema::create('beauty_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('salon_id')->constrained('beauty_salons')->onDelete('cascade');
                $table->string('name')->index();
                $table->string('sku')->unique()->index();
                $table->text('description')->nullable();
                $table->bigInteger('price')->index()->comment('Price in kopeks');
                $table->integer('current_stock')->default(0);
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Products for retail in salons (Domain: Beauty)');
            });
        }

        // 7. Портфолио
        if (!Schema::hasTable('beauty_portfolio_items')) {
            Schema::create('beauty_portfolio_items', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('master_id')->constrained('masters')->onDelete('cascade');
                $table->string('image_url');
                $table->text('description')->nullable();
                $table->jsonb('tags')->nullable()->comment('Tags: hair, wedding, nails, etc.');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Master portfolio items (Domain: Beauty)');
            });
        }

        // 8. Отзывы (Beauty Reviews)
        if (!Schema::hasTable('beauty_reviews')) {
            Schema::create('beauty_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('salon_id')->nullable()->constrained('beauty_salons')->onDelete('cascade');
                $table->foreignId('master_id')->nullable()->constrained('masters')->onDelete('cascade');
                $table->foreignId('appointment_id')->nullable()->constrained('beauty_appointments')->onDelete('set null');
                $table->integer('rating')->unsigned()->index();
                $table->text('comment')->nullable();
                $table->jsonb('photos')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Customer reviews for salons and masters');
            });
        }

        // 9. B2B Заказы (Beauty Supplies Orders)
        if (!Schema::hasTable('b2b_beauty_orders')) {
            Schema::create('b2b_beauty_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
                $table->foreignId('supplier_id')->constrained('tenants')->onDelete('cascade');
                $table->bigInteger('total_price')->comment('In kopeks');
                $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending')->index();
                $table->jsonb('items')->comment('List of beauty_products and quantities');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('B2B supply orders for beauty businesses');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_beauty_orders');
        Schema::dropIfExists('beauty_reviews');
        Schema::dropIfExists('beauty_portfolio_items');
        Schema::dropIfExists('beauty_products');
        Schema::dropIfExists('beauty_consumables');
        Schema::dropIfExists('beauty_appointments');
        Schema::dropIfExists('beauty_services');
        Schema::dropIfExists('masters');
        Schema::dropIfExists('beauty_salons');
    }
};


