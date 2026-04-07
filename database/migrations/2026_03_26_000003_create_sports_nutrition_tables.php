<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for SportsNutrition vertical (Layer 1/9).
 * Implements 6+ tables for SportsNutritionStore, Product, Category, SubscriptionBox, Review, Consumable.
 * Includes JSONB support for allergens, nutrition facts, and AI manufacturing specs.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Sports Nutrition Stores
        if (!Schema::hasTable('sports_nutrition_stores')) {
            Schema::create('sports_nutrition_stores', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->uuid('uuid')->unique()->index();
                $blueprint->foreignId('tenant_id')->index();
                $blueprint->string('name')->comment('Store brand name.');
                $blueprint->string('license_number')->nullable()->comment('Pharmaceutical or supplement license.');
                $blueprint->string('location_address')->nullable();
                $blueprint->jsonb('working_hours')->nullable();
                $blueprint->jsonb('tags')->nullable()->comment('Retailer type, focus areas.');
                $blueprint->decimal('rating', 3, 2)->default(0);
                $blueprint->string('correlation_id')->nullable()->index();
                $blueprint->timestamps();
                $blueprint->softDeletes();

                $blueprint->comment('Physical or digital stores specializing in sports supplements.');
            });
        }

        // 2. Nutrition Categories
        if (!Schema::hasTable('sports_nutrition_categories')) {
            Schema::create('sports_nutrition_categories', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->uuid('uuid')->unique()->index();
                $blueprint->foreignId('tenant_id')->index();
                $blueprint->string('name')->comment('Category name (e.g., Whey Protein, BCAA).');
                $blueprint->string('slug')->unique();
                $blueprint->text('description')->nullable();
                $blueprint->boolean('is_active')->default(true);
                $blueprint->timestamps();

                $blueprint->comment('Classification for sports nutrition and supplements.');
            });
        }

        // 3. Nutrition Products
        if (!Schema::hasTable('sports_nutrition_products')) {
            Schema::create('sports_nutrition_products', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->uuid('uuid')->unique()->index();
                $blueprint->foreignId('tenant_id')->index();
                $blueprint->foreignId('store_id')->constrained('sports_nutrition_stores')->onDelete('cascade');
                $blueprint->foreignId('category_id')->constrained('sports_nutrition_categories');
                
                $blueprint->string('name')->index();
                $blueprint->string('sku')->unique()->index();
                $blueprint->string('brand')->index();
                $blueprint->text('description')->nullable();
                
                // Commercials (Prices in kopecks)
                $blueprint->bigInteger('price_b2c')->comment('Retail price in kopecks.');
                $blueprint->bigInteger('price_b2b')->comment('Wholesale/Coach price in kopecks.');
                $blueprint->integer('stock_quantity')->default(0);
                
                // Specifications
                $blueprint->string('form_factor')->comment('Powder, Capsules, Liquid.');
                $blueprint->integer('servings_count')->default(1);
                $blueprint->jsonb('nutrition_facts')->nullable()->comment('Calories, Protein, Fat, Carbs per serving.');
                $blueprint->jsonb('allergens')->nullable()->comment('Milk, Soy, Nuts, etc.');
                $blueprint->date('expiry_date')->index();
                
                // Features
                $blueprint->boolean('is_vegan')->default(false);
                $blueprint->boolean('is_gmo_free')->default(true);
                $blueprint->boolean('is_published')->default(false);
                
                $blueprint->jsonb('tags')->nullable();
                $blueprint->string('correlation_id')->nullable()->index();
                $blueprint->timestamps();
                $blueprint->softDeletes();

                $blueprint->index(['tenant_id', 'is_published', 'category_id'], 'sn_catalog_filter');
                $blueprint->comment('Comprehensive catalog of supplements with nutrition and allergen data.');
            });
        }

        // 4. Subscription Boxes (Bundles)
        if (!Schema::hasTable('sports_nutrition_subscription_boxes')) {
            Schema::create('sports_nutrition_subscription_boxes', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->uuid('uuid')->unique()->index();
                $blueprint->foreignId('tenant_id')->index();
                $blueprint->string('name')->comment('Goal name (e.g., Lean Muscle Pack).');
                $blueprint->text('description')->nullable();
                $blueprint->bigInteger('price_monthly')->comment('Subscription cost per month.');
                $blueprint->jsonb('included_skus')->comment('List of product SKUs included.');
                $blueprint->string('training_goal')->index()->comment('Target goal: bulking, cutting, endurance.');
                $blueprint->boolean('is_active')->default(true);
                $blueprint->string('correlation_id')->nullable()->index();
                $blueprint->timestamps();

                $blueprint->comment('AI-driven or curated supplement subscription packs.');
            });
        }

        // 5. Consumables (Ingredients for manufacturing/tracking)
        if (!Schema::hasTable('sports_nutrition_consumables')) {
            Schema::create('sports_nutrition_consumables', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->uuid('uuid')->unique()->index();
                $blueprint->foreignId('tenant_id')->index();
                $blueprint->string('name')->comment('Raw ingredient name (e.g., Creatine Monohydrate).');
                $blueprint->decimal('stock_kg', 12, 4)->default(0);
                $blueprint->decimal('min_threshold', 12, 4)->default(1.0);
                $blueprint->string('purity_percentage')->nullable();
                $blueprint->string('correlation_id')->nullable()->index();
                $blueprint->timestamps();

                $blueprint->comment('Inventory of raw ingredients for supplement manufacturing.');
            });
        }

        // 6. Supplement Reviews
        if (!Schema::hasTable('sports_nutrition_reviews')) {
            Schema::create('sports_nutrition_reviews', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->uuid('uuid')->unique()->index();
                $blueprint->foreignId('tenant_id')->index();
                $blueprint->foreignId('user_id')->index();
                $blueprint->foreignId('product_id')->constrained('sports_nutrition_products');
                $blueprint->integer('rating')->default(5);
                $blueprint->text('comment')->nullable();
                $blueprint->jsonb('impact_data')->nullable()->comment('User reported energy, recovery, taste score.');
                $blueprint->boolean('is_verified_purchase')->default(false);
                $blueprint->string('correlation_id')->nullable()->index();
                $blueprint->timestamps();

                $blueprint->comment('Customer feedback on supplements with performance impact metadata.');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sports_nutrition_reviews');
        Schema::dropIfExists('sports_nutrition_consumables');
        Schema::dropIfExists('sports_nutrition_subscription_boxes');
        Schema::dropIfExists('sports_nutrition_products');
        Schema::dropIfExists('sports_nutrition_categories');
        Schema::dropIfExists('sports_nutrition_stores');
    }
};


