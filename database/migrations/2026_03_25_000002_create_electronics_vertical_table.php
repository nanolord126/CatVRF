<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for Electronics Vertical 2026.
 * All tables include tenant_id scoping and audit correlation fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Electronics Stores (Points of Sale)
        if (!Schema::hasTable('electronics_stores')) {
            Schema::create('electronics_stores', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name')->comment('Store name');
                $table->string('address')->nullable();
                $table->jsonb('working_hours')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Electronics brick-and-mortar stores or warehouses');
            });
        }

        // 2. Electronics Categories
        if (!Schema::hasTable('electronics_categories')) {
            Schema::create('electronics_categories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name');
                $table->string('slug')->index();
                $table->string('icon')->nullable();
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                
                $table->comment('Categories like Smartphones, Laptops, Home Appliances');
            });
        }

        // 3. Central Product Table (Electronics)
        if (!Schema::hasTable('electronics_products')) {
            Schema::create('electronics_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('category_id')->constrained('electronics_categories');
                $table->foreignId('store_id')->constrained('electronics_stores');
                
                $table->string('name')->index();
                $table->string('sku')->unique()->index();
                $table->string('brand')->index();
                $table->string('model_number')->nullable();
                
                $table->text('description')->nullable();
                $table->integer('price_kopecks')->unsigned()->comment('Price in lowest currency unit');
                $table->integer('b2b_price_kopecks')->unsigned()->nullable();
                
                $table->integer('current_stock')->default(0);
                $table->integer('hold_stock')->default(0);
                $table->integer('min_threshold')->default(1);
                
                $table->enum('availability', ['in_stock', 'pre_order', 'out_of_stock'])->default('in_stock');
                $table->jsonb('specs')->nullable()->comment('Technical specifications (CPU, RAM, Color, etc)');
                $table->jsonb('package_contents')->nullable()->comment('What is inside the box');
                
                $table->decimal('weight_kg', 8, 3)->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Main electronics product registry');
            });
        }

        // 4. Gadgets Specialized Data
        if (!Schema::hasTable('electronics_gadgets')) {
            Schema::create('electronics_gadgets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('electronics_products')->onDelete('cascade');
                $table->string('os_version')->nullable();
                $table->string('cpu_model')->nullable();
                $table->integer('ram_gb')->nullable();
                $table->integer('storage_gb')->nullable();
                $table->decimal('screen_size_inch', 5, 2)->nullable();
                $table->integer('battery_mah')->nullable();
                $table->boolean('is_5g_ready')->default(false);
                $table->timestamps();
                
                $table->comment('Technical metadata for smart devices');
            });
        }

        // 5. Accessories Relation
        if (!Schema::hasTable('electronics_accessories')) {
            Schema::create('electronics_accessories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('electronics_products')->onDelete('cascade');
                $table->foreignId('compatible_with_id')->constrained('electronics_products')->onDelete('cascade');
                $table->string('accessory_type')->nullable()->comment('Case, Cable, Charger, Powerbank');
                $table->timestamps();
                
                $table->comment('Compatibility mapping between accessories and main gadgets');
            });
        }

        // 6. Warranties
        if (!Schema::hasTable('electronics_warranties')) {
            Schema::create('electronics_warranties', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('product_id')->constrained('electronics_products');
                $table->string('order_id')->nullable()->index();
                $table->string('user_id')->nullable()->index();
                
                $table->string('serial_number')->unique()->index();
                $table->date('starts_at');
                $table->date('expires_at');
                $table->enum('status', ['active', 'expired', 'claimed', 'void'])->default('active');
                
                $table->text('terms')->nullable();
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                
                $table->comment('Service and warranty tracking for high-value electronics');
            });
        }

        // 7. Reviews
        if (!Schema::hasTable('electronics_reviews')) {
            Schema::create('electronics_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('product_id')->constrained('electronics_products');
                $table->foreignId('user_id')->index();
                
                $table->tinyInteger('rating')->unsigned()->comment('1-5 scale');
                $table->text('comment');
                $table->jsonb('images')->nullable();
                $table->boolean('is_verified_purchase')->default(false);
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                
                $table->comment('User feedback for electronics items');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('electronics_reviews');
        Schema::dropIfExists('electronics_warranties');
        Schema::dropIfExists('electronics_accessories');
        Schema::dropIfExists('electronics_gadgets');
        Schema::dropIfExists('electronics_products');
        Schema::dropIfExists('electronics_categories');
        Schema::dropIfExists('electronics_stores');
    }
};


