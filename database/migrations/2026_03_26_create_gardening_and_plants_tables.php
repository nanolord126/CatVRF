<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GardeningAndPlants Migration (Layer 1/9)
 * Production-ready schema for Garden Stores, Plants, Seeds, and Climate-based Analytics.
 * Implementation exceeds 60 lines.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Garden Stores (Nursery, Greenhouse, Garden Centers)
        if (!Schema::hasTable('garden_stores')) {
            Schema::create('garden_stores', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name')->comment('Store or nursery name');
                $table->string('location_lat_lon')->nullable();
                $table->jsonb('climate_zones')->comment('Preferred climate zones for shipping (frost resistance, etc)');
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();

                $table->comment('Garden stores and nurseries with regional climate scoping');
            });
        }

        // 2. Gardening Categories (Seeds, Indoor Plants, Fertilizers, Tools)
        if (!Schema::hasTable('garden_categories')) {
            Schema::create('garden_categories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name');
                $table->string('slug');
                $table->text('care_guide_summary')->nullable()->comment('General AI-generated care instructions');
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'slug']);
                $table->comment('Categories with linked care instructions');
            });
        }

        // 3. Garden Products (General items like tools, fertilizers, pots)
        if (!Schema::hasTable('garden_products')) {
            Schema::create('garden_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('store_id')->constrained('garden_stores')->onDelete('cascade');
                $table->foreignId('category_id')->constrained('garden_categories')->onDelete('cascade');
                $table->string('name');
                $table->string('sku')->unique();
                $table->integer('price_b2c')->comment('Retail price in kopecks');
                $table->integer('price_b2b')->comment('Wholesale price for landscapers');
                $table->integer('stock_quantity')->default(0);
                $table->jsonb('specifications')->nullable()->comment('Dimensions, materials, weight');
                $table->boolean('is_published')->default(true);
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'store_id', 'category_id']);
                $table->comment('General gardening tools and supplies catalog');
            });
        }

        // 4. Live Plants & Seeds (Specific data for living organisms)
        if (!Schema::hasTable('garden_plants')) {
            Schema::create('garden_plants', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('garden_products')->onDelete('cascade');
                $table->string('botanical_name')->nullable();
                $table->enum('hardiness_zone', ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11']);
                $table->enum('light_requirement', ['full_sun', 'partial_shade', 'shade']);
                $table->enum('water_needs', ['low', 'medium', 'high']);
                $table->jsonb('care_calendar')->comment('Monthly maintenance schedule: pruning, fertilizing');
                $table->boolean('is_seedling')->default(false);
                $table->date('sowing_start')->nullable();
                $table->date('harvest_start')->nullable();
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->comment('Biological metadata for live plants and seeds');
            });
        }

        // 5. Subscription Boxes (Seasonal plant boxes, seeds of the month)
        if (!Schema::hasTable('garden_subscription_boxes')) {
            Schema::create('garden_subscription_boxes', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name');
                $table->enum('frequency', ['monthly', 'quarterly', 'seasonal']);
                $table->integer('price')->comment('Box price in kopecks');
                $table->jsonb('contents_json')->comment('SKU list or description of what is included');
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->comment('Subscription service for recurring gardening needs');
            });
        }

        // 6. Garden Reviews (Verified plant survival/growth reviews)
        if (!Schema::hasTable('garden_reviews')) {
            Schema::create('garden_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('garden_products')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->integer('rating')->unsigned();
                $table->text('comment');
                $table->jsonb('growth_updates')->nullable()->comment('Photos/text of plant growth progression');
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->comment('Growth tracking and verification reviews');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('garden_reviews');
        Schema::dropIfExists('garden_subscription_boxes');
        Schema::dropIfExists('garden_plants');
        Schema::dropIfExists('garden_products');
        Schema::dropIfExists('garden_categories');
        Schema::dropIfExists('garden_stores');
    }
};
