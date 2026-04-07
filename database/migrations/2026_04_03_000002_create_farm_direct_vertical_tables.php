<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FarmDirect vertical tables — CatVRF 2026
 * Models: Farm, FarmProduct, FarmOrder
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_direct_farms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->string('region');
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lon', 11, 8)->nullable();
            $table->text('description')->nullable();
            $table->enum('certification', ['none', 'organic', 'bio', 'eco'])->default('none');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status', 'region']);
        });

        Schema::create('farm_direct_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('farm_id')->constrained('farm_direct_farms')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category'); // vegetables, fruits, meat, dairy, etc.
            $table->string('unit');     // kg, piece, liter
            $table->decimal('price', 10, 2);
            $table->decimal('price_b2b', 10, 2)->nullable();
            $table->integer('available_quantity')->default(0);
            $table->integer('min_order_quantity')->default(1);
            $table->string('season')->nullable(); // spring, summer, autumn, winter, all_year
            $table->boolean('is_seasonal')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'category', 'is_active']);
        });

        Schema::create('farm_direct_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('farm_id')->constrained('farm_direct_farms')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('idempotency_key')->unique()->nullable();
            $table->enum('status', ['pending', 'confirmed', 'harvesting', 'packed', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->decimal('total_price', 14, 2);
            $table->timestamp('delivery_date')->nullable();
            $table->json('delivery_address');
            $table->boolean('is_subscription')->default(false);
            $table->string('subscription_period')->nullable(); // weekly, biweekly, monthly
            $table->boolean('is_b2b')->default(false);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_direct_orders');
        Schema::dropIfExists('farm_direct_products');
        Schema::dropIfExists('farm_direct_farms');
    }
};
