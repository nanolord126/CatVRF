<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for Furniture & Interior Domain (2026 Production Ready)
 * Vertical: Furniture, Decor, Interior Items.
 * Requirement: Idempotent, detailed comments, correlation_id, JSONB for analytics.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Furniture Stores (Shops/Vendors)
        if (!Schema::hasTable('furniture_stores')) {
            Schema::create('furniture_stores', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name')->comment('Name of the furniture shop/factory');
                $table->string('slug')->unique();
                $table->string('address')->nullable();
                $table->jsonb('schedule_json')->nullable()->comment('Operating hours');
                $table->decimal('rating', 3, 2)->default(0.00);
                $table->boolean('is_verified')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Furniture stores and manufacturers within a tenant');
                $table->index(['tenant_id', 'is_verified']);
            });
        }

        // 2. Furniture Categories
        if (!Schema::hasTable('furniture_categories')) {
            Schema::create('furniture_categories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name');
                $table->string('slug');
                $table->string('description', 1000)->nullable();
                $table->integer('sort_order')->default(0);
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'slug']);
                $table->comment('Product categories (Sofas, Chairs, Lightning, etc)');
            });
        }

        // 3. Room Types (AI Interior Context)
        if (!Schema::hasTable('furniture_room_types')) {
            Schema::create('furniture_room_types', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('name')->comment('Living Room, Kitchen, Bedroom, Office');
                $table->string('slug')->unique();
                $table->jsonb('style_presets')->nullable()->comment('Default AI styles for this room');
                $table->timestamps();

                $table->comment('Room types for AI Interior Constructor context');
            });
        }

        // 4. Furniture Products
        if (!Schema::hasTable('furniture_products')) {
            Schema::create('furniture_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('furniture_store_id')->constrained('furniture_stores')->onDelete('cascade');
                $table->foreignId('furniture_category_id')->constrained('furniture_categories')->onDelete('cascade');
                
                $table->string('name')->index();
                $table->string('sku')->unique();
                $table->text('description')->nullable();
                $table->jsonb('properties')->nullable()->comment('Dimensions, materials, weight, color');
                
                $table->bigInteger('price_b2c')->comment('Price in kopecks for retail');
                $table->bigInteger('price_b2b')->comment('Wholesale/Business price in kopecks');
                $table->integer('stock_quantity')->default(0);
                $table->integer('hold_stock')->default(0);
                
                $table->boolean('is_oversized')->default(false)->comment('Requires special delivery');
                $table->boolean('requires_assembly')->default(false);
                $table->bigInteger('assembly_cost')->default(0)->comment('In kopecks');
                
                $table->string('threed_preview_url')->nullable()->comment('Path to 3D model/GLB');
                $table->jsonb('recommended_room_types')->nullable()->comment('Array of room_type_ids');
                
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'furniture_store_id']);
                $table->index(['price_b2c', 'stock_quantity']);
                $table->comment('Main inventory for Furniture Vertical');
            });
        }

        // 5. Furniture Custom Orders (AI-generated designs)
        if (!Schema::hasTable('furniture_custom_orders')) {
            Schema::create('furniture_custom_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users');
                $table->foreignId('room_type_id')->constrained('furniture_room_types');
                
                $table->string('status')->default('pending')->index(); // pending, design_ready, paid, in_production, shipping, delivered
                $table->bigInteger('total_amount')->comment('In kopecks');
                
                $table->jsonb('ai_specification')->nullable()->comment('AI-selected products and positions');
                $table->jsonb('room_photo_analysis')->nullable();
                $table->boolean('include_assembly')->default(true);
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Custom orders generated via AI Interior Constructor');
            });
        }

        // 6. Furniture Reviews
        if (!Schema::hasTable('furniture_reviews')) {
            Schema::create('furniture_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users');
                $table->foreignId('furniture_product_id')->constrained('furniture_products');
                
                $table->integer('rating')->default(5);
                $table->text('comment')->nullable();
                $table->jsonb('photos')->nullable();
                $table->boolean('is_verified_purchase')->default(false);
                
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->index(['furniture_product_id', 'rating']);
                $table->comment('Customer reviews for furniture items');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('furniture_reviews');
        Schema::dropIfExists('furniture_custom_orders');
        Schema::dropIfExists('furniture_products');
        Schema::dropIfExists('furniture_room_types');
        Schema::dropIfExists('furniture_categories');
        Schema::dropIfExists('furniture_stores');
    }
};


