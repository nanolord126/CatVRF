<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MeatShops vertical tables — CatVRF 2026
 * Models: MeatShop, MeatProduct, MeatOrder, MeatConsumable, MeatBoxSubscription
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meat_shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lon', 11, 8)->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->boolean('is_halal')->default(false);
            $table->boolean('is_kosher')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('meat_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('shop_id')->constrained('meat_shops')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->string('animal_type'); // beef, pork, chicken, lamb, etc.
            $table->string('cut_type');    // steak, ribs, fillet, etc.
            $table->string('unit');        // kg, piece
            $table->decimal('price_per_unit', 10, 2);
            $table->decimal('price_b2b', 10, 2)->nullable();
            $table->integer('available_grams')->default(0);
            $table->integer('min_order_grams')->default(500);
            $table->boolean('is_halal')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'animal_type', 'is_active']);
        });

        Schema::create('meat_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shop_id')->constrained('meat_shops')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('idempotency_key')->unique()->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cutting', 'packed', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->decimal('total_price', 14, 2);
            $table->json('delivery_address');
            $table->timestamp('delivery_at')->nullable();
            $table->boolean('is_b2b')->default(false);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status', 'user_id']);
        });

        Schema::create('meat_box_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shop_id')->constrained('meat_shops')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->enum('period', ['weekly', 'biweekly', 'monthly']);
            $table->decimal('price', 10, 2);
            $table->integer('weight_grams');
            $table->json('preferences')->nullable(); // animal types, cuts
            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');
            $table->timestamp('next_delivery_at')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meat_box_subscriptions');
        Schema::dropIfExists('meat_orders');
        Schema::dropIfExists('meat_products');
        Schema::dropIfExists('meat_shops');
    }
};
