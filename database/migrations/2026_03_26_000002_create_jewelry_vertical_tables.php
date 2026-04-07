<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * JewelryVerticalSchema (Layer 1/9)
 * Comprehensive schema for the Jewelry Marketplace & Custom Manufacturing.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Jewelry Stores / Workshops
        if (!Schema::hasTable('jewelry_stores')) {
            Schema::create('jewelry_stores', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('business_group_id')->nullable()->index();
                $table->string('name')->comment('Name of the store or workshop');
                $table->string('license_number')->nullable()->comment('Jewelry manufacturing/retail license');
                $table->jsonb('settings')->nullable()->comment('Store specific production settings');
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Jewelry stores, workshops and boutiques.');
            });
        }

        // 2. Jewelry Categories
        if (!Schema::hasTable('jewelry_categories')) {
            Schema::create('jewelry_categories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name');
                $table->string('slug');
                $table->integer('sort_order')->default(0);
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                
                $table->comment('Categories like Rings, Necklaces, Earrings, etc.');
            });
        }

        // 3. Jewelry Collections
        if (!Schema::hasTable('jewelry_collections')) {
            Schema::create('jewelry_collections', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('store_id')->constrained('jewelry_stores');
                $table->string('name');
                $table->text('description')->nullable();
                $table->jsonb('theme_data')->nullable()->comment('AI-suggested theme parameters');
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                
                $table->comment('Seasonal or thematic jewelry collections.');
            });
        }

        // 4. Jewelry Products
        if (!Schema::hasTable('jewelry_products')) {
            Schema::create('jewelry_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('store_id')->constrained('jewelry_stores');
                $table->foreignId('category_id')->constrained('jewelry_categories');
                $table->foreignId('collection_id')->nullable()->constrained('jewelry_collections');
                
                $table->string('name');
                $table->string('sku')->index();
                $table->text('description')->nullable();
                
                // Pricing (Kopecks)
                $table->integer('price_b2c')->comment('Standard retail price');
                $table->integer('price_b2b')->comment('Wholesale price for bulk partners');
                $table->integer('stock_quantity')->default(0);
                
                // Technical Specifications
                $table->string('metal_type')->comment('Gold, Silver, Platinum, etc.');
                $table->string('metal_fineness')->comment('585, 750, 925, etc.');
                $table->float('weight_grams')->nullable();
                $table->jsonb('gemstones')->nullable()->comment('List of stones with carats, clarity, etc.');
                
                // Features
                $table->boolean('has_certification')->default(false);
                $table->string('certificate_number')->nullable();
                $table->boolean('is_customizable')->default(false);
                $table->boolean('is_gift_wrapped')->default(false);
                $table->boolean('is_published')->default(false);
                
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Inventory of jewelry products (finished items).');
            });
        }

        // 5. Jewelry Custom Orders (AI-Driven Designs)
        if (!Schema::hasTable('jewelry_custom_orders')) {
            Schema::create('jewelry_custom_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('store_id')->constrained('jewelry_stores');
                $table->foreignId('user_id')->index();
                
                $table->string('status')->default('draft')->index(); // draft, pending, paid, in_production, ready, shipped
                $table->string('customer_name')->nullable();
                $table->string('customer_phone')->nullable();
                
                $table->integer('estimated_price')->comment('Initial AI estimation');
                $table->integer('final_price')->nullable();
                
                $table->jsonb('ai_specification')->nullable()->comment('Detailed design specs from AI Constructor');
                $table->text('user_notes')->nullable();
                $table->string('reference_photo_path')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Customer requests for unique custom-made jewelry.');
            });
        }

        // 6. Jewelry Reviews
        if (!Schema::hasTable('jewelry_reviews')) {
            Schema::create('jewelry_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('product_id')->constrained('jewelry_products');
                $table->foreignId('user_id')->index();
                $table->integer('rating')->default(5);
                $table->text('comment')->nullable();
                $table->jsonb('photos')->nullable();
                $table->boolean('is_verified_purchase')->default(true);
                $table->string('correlation_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jewelry_reviews');
        Schema::dropIfExists('jewelry_custom_orders');
        Schema::dropIfExists('jewelry_products');
        Schema::dropIfExists('jewelry_collections');
        Schema::dropIfExists('jewelry_categories');
        Schema::dropIfExists('jewelry_stores');
    }
};


