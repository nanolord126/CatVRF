<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HouseholdGoods vertical tables — CatVRF 2026
 * Models: AppliancePart, ApplianceRepairOrder + product catalog
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('household_goods_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category'); // kitchen, cleaning, storage, tools, etc.
            $table->string('brand')->nullable();
            $table->string('sku')->nullable()->unique();
            $table->decimal('price', 10, 2);
            $table->decimal('price_b2b', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('attributes')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'category', 'is_active']);
        });

        Schema::create('household_goods_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('idempotency_key')->unique()->nullable();
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'])->default('pending');
            $table->decimal('total_price', 14, 2);
            $table->json('delivery_address');
            $table->boolean('is_b2b')->default(false);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status', 'user_id']);
        });

        Schema::create('household_appliance_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->string('part_number')->nullable()->index();
            $table->string('appliance_type'); // refrigerator, washing_machine, dishwasher, etc.
            $table->string('brand_compatibility')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('stock_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_appliance_parts');
        Schema::dropIfExists('household_goods_orders');
        Schema::dropIfExists('household_goods_products');
    }
};
