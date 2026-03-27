<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BabyAndKids Vertical Migration 2026 - Production Ready.
 * Includes Stores, Products, Toys, Clothing, Centers, Events, and Reviews.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Kids Stores & Boutiques
        if (!Schema::hasTable('kids_stores')) {
            Schema::create('kids_stores', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->string('business_group_id')->nullable()->index();
                $table->string('name')->comment('Store or Boutique name');
                $table->enum('type', ['retail', 'online', 'resale', 'boutique', 'center'])->default('retail');
                $table->string('address')->nullable();
                $table->jsonb('geo_point')->nullable(); // Point: [lat, lon]
                $table->jsonb('schedule')->nullable(); // Opening hours
                $table->decimal('rating', 3, 2)->default(5.00);
                $table->integer('review_count')->default(0);
                $table->boolean('is_verified')->default(false);
                $table->jsonb('safety_certificates')->nullable(); // Safety compliance docs
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['tenant_id', 'type']);
                $table->comment('Kids-focused retail and service points');
            });
        }

        // 2. Base Kids Product (General: Furniture, Hygiene, etc.)
        if (!Schema::hasTable('kids_products')) {
            Schema::create('kids_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->foreignId('store_id')->constrained('kids_stores')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->integer('price')->comment('Price in kopecks');
                $table->integer('stock_quantity')->default(0);
                $table->string('sku')->unique();
                $table->string('barcode')->nullable()->index();
                $table->jsonb('age_range')->comment('min/max_months');
                $table->enum('safety_class', ['A', 'B', 'C'])->default('A');
                $table->jsonb('material_details')->nullable();
                $table->string('origin_country')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['store_id', 'price']);
                $table->comment('Base catalog for children goods');
            });
        }

        // 3. Specialized Toys
        if (!Schema::hasTable('kids_toys')) {
            Schema::create('kids_toys', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('product_id')->constrained('kids_products')->onDelete('cascade');
                $table->string('toy_type')->comment('educational, soft, robotic, construction');
                $table->string('brand')->nullable()->index();
                $table->jsonb('parts_count')->nullable();
                $table->boolean('requires_batteries')->default(false);
                $table->jsonb('educational_goals')->nullable(); // Logic, Motor skills, etc.
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Specific toy metadata and characteristics');
            });
        }

        // 4. Kids Clothing
        if (!Schema::hasTable('kids_clothing')) {
            Schema::create('kids_clothing', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('product_id')->constrained('kids_products')->onDelete('cascade');
                $table->string('size_code')->comment('e.g., 68, 80, 110, 152');
                $table->string('gender')->nullable(); // boys, girls, unisex
                $table->string('season')->nullable(); // winter, summer, demi
                $table->jsonb('fabric_composition')->nullable();
                $table->string('color_name')->nullable();
                $table->string('style_type')->nullable(); // formal, casual, sport
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Children apparel and fashion metadata');
            });
        }

        // 5. Entertainment & Education Centers
        if (!Schema::hasTable('kids_centers')) {
            Schema::create('kids_centers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->string('name');
                $table->string('address');
                $table->jsonb('geo_point')->nullable();
                $table->integer('capacity_max')->default(50);
                $table->jsonb('age_limits')->nullable();
                $table->jsonb('safety_protocols')->nullable(); // Fire safety, CCTV, Medical staff
                $table->decimal('hourly_rate', 10, 2)->default(0.00);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->comment('Playgrounds, kindergartens, and development centers');
            });
        }

        // 6. Center Events / Birthdays / Masters
        if (!Schema::hasTable('kids_events')) {
            Schema::create('kids_events', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->foreignId('center_id')->constrained('kids_centers')->onDelete('cascade');
                $table->string('title');
                $table->text('description')->nullable();
                $table->dateTime('start_at');
                $table->dateTime('end_at');
                $table->integer('price')->comment('Event package price');
                $table->integer('max_guests')->default(20);
                $table->string('organizer_id')->nullable()->comment('User ID or Specialist ID');
                $table->jsonb('included_services')->nullable(); // Animators, Food, Decoration
                $table->string('status')->default('scheduled'); // scheduled, active, completed, cancelled
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['center_id', 'start_at']);
                $table->comment('Birthdays, masterclasses, and group activities');
            });
        }

        // 7. Verified Reviews
        if (!Schema::hasTable('kids_reviews')) {
            Schema::create('kids_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->string('user_id')->index();
                $table->morphs('reviewable'); // product or center
                $table->integer('rating')->default(5);
                $table->text('comment')->nullable();
                $table->jsonb('photos')->nullable();
                $table->boolean('is_verified_purchase')->default(true);
                $table->boolean('is_approved')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['reviewable_id', 'reviewable_type', 'rating']);
                $table->comment('Parent feedback and product quality ratings');
            });
        }

        // 8. Gift Certificates & B2B Packages
        if (!Schema::hasTable('kids_vouchers')) {
            Schema::create('kids_vouchers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->string('code')->unique();
                $table->integer('initial_balance')->comment('Amount in kopecks');
                $table->integer('current_balance');
                $table->enum('type', ['gift', 'subscription', 'corporate'])->default('gift');
                $table->dateTime('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Monetary instruments for parents and B2B clients');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kids_vouchers');
        Schema::dropIfExists('kids_reviews');
        Schema::dropIfExists('kids_events');
        Schema::dropIfExists('kids_centers');
        Schema::dropIfExists('kids_clothing');
        Schema::dropIfExists('kids_toys');
        Schema::dropIfExists('kids_products');
        Schema::dropIfExists('kids_stores');
    }
};
