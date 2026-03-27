<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration HomeApplianceService 2026.
 * Кодировка: UTF-8 без BOM, CRLF.
 * Канон: UUID, tenant_id, correlation_id, business_group_id, tags.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Справочник брендов и типов техники
        if (!Schema::hasTable('appliance_brands')) {
            Schema::create('appliance_brands', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name');
                $table->jsonb('categories')->comment('Стриральные машины, Холодильники и т.д.');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Бренды бытовой техники (Samsung, LG, Bosch)');
            });
        }

        // 2. Каталог запчастей/расходников
        if (!Schema::hasTable('appliance_parts')) {
            Schema::create('appliance_parts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->string('sku')->index();
                $table->string('name');
                $table->integer('price_kopecks');
                $table->integer('stock_quantity')->default(0);
                $table->integer('min_stock_threshold')->default(5);
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Склад запчастей для ремонта');
            });
        }

        // 3. Заказ-наряды на ремонт
        if (!Schema::hasTable('appliance_repair_orders')) {
            Schema::create('appliance_repair_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('master_id')->nullable()->index();
                
                $table->string('appliance_type')->index()->comment('washing_machine, fridge, AC');
                $table->string('brand_name')->nullable();
                $table->string('model_number')->nullable();
                
                $table->text('issue_description');
                $table->jsonb('ai_estimate')->nullable()->comment('Предварительная оценка AI');
                
                $table->string('status')->default('pending')->index(); // pending, diagnostic, in_repair, quality_check, completed, cancelled
                $table->boolean('is_b2b')->default(false)->index();
                
                $table->integer('labor_cost_kopecks')->default(0);
                $table->integer('parts_cost_kopecks')->default(0);
                $table->integer('total_cost_kopecks')->default(0);
                
                $table->timestamp('visit_scheduled_at')->nullable();
                $table->timestamp('repair_started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('warranty_expires_at')->nullable();
                
                $table->jsonb('address_json')->comment('Адрес выезда мастера');
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Заявки на ремонт бытовой техники 2026');
            });
        }

        // 4. Использование запчастей в ремонте
        if (!Schema::hasTable('appliance_repair_parts')) {
            Schema::create('appliance_repair_parts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('repair_order_id')->constrained('appliance_repair_orders')->onDelete('cascade');
                $table->foreignId('part_id')->constrained('appliance_parts');
                $table->integer('quantity');
                $table->integer('price_at_moment_kopecks');
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->comment('Списание запчастей под ремонт');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appliance_repair_parts');
        Schema::dropIfExists('appliance_repair_orders');
        Schema::dropIfExists('appliance_parts');
        Schema::dropIfExists('appliance_brands');
    }
};
