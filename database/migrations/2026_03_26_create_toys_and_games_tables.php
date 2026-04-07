<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration (Layer 1/9)
 * Full relational schema for the ToysAndGames vertical.
 * Includes B2C/B2B pricing, age-group taxonomy, safety standards, and gift packaging.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('toy_stores')) {
            Schema::create('toy_stores', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name');
                $table->string('location')->nullable();
                $table->jsonb('metadata')->nullable(); // Working hours, contact info
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Toy stores and physical distributions centers for kids products.');
            });
        }

        if (!Schema::hasTable('toy_categories')) {
            Schema::create('toy_categories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Hierarchy for toys: educational, sensory, puzzles, board games.');
            });
        }

        if (!Schema::hasTable('age_groups')) {
            Schema::create('age_groups', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name'); // e.g., 0-3, 3-6, 7-12, 13+
                $table->integer('min_age_months')->default(0);
                $table->integer('max_age_months')->default(216); // 18 years
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Target age demographics for toy matching AI.');
            });
        }

        if (!Schema::hasTable('toys')) {
            Schema::create('toys', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('store_id')->constrained('toy_stores');
                $table->foreignId('category_id')->constrained('toy_categories');
                $table->foreignId('age_group_id')->constrained('age_groups');
                $table->string('title');
                $table->string('sku')->unique()->index();
                $table->text('description')->nullable();
                $table->bigInteger('price_b2c')->comment('Retail price in kopecks');
                $table->bigInteger('price_b2b')->comment('Wholesale price for kindergartens/schools in kopecks');
                $table->integer('stock_quantity')->default(0);
                $table->string('safety_certification')->nullable()->comment('Certificate of conformity');
                $table->string('material_type')->nullable(); // Plastic, Wooden, Plush
                $table->boolean('is_gift_wrappable')->default(true);
                $table->boolean('is_active')->default(true);
                $table->jsonb('metadata')->nullable(); // Features: battery type, dimensions, weight
                $table->jsonb('tags')->nullable(); // AI tagging: sensory, logic, team-play
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Core product entity for the ToysAndGames vertical.');
            });
        }

        if (!Schema::hasTable('toy_subscription_boxes')) {
            Schema::create('toy_subscription_boxes', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->foreignId('age_group_id')->constrained('age_groups');
                $table->integer('monthly_limit')->default(3);
                $table->string('status')->default('active')->index(); // active, paused, cancelled
                $table->boolean('is_paid')->default(false);
                $table->timestamp('next_delivery_at')->nullable();
                $table->timestamp('last_sent_at')->nullable();
                $table->jsonb('metadata')->nullable(); // Preferences: avoid noise, specific materials
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Subscription service for periodic toy rotate boxes.');
            });
        }

        if (!Schema::hasTable('toy_orders')) {
            Schema::create('toy_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('b2b_company_id')->nullable()->index(); // Kindergarten / Educational NGO
                $table->foreignId('store_id')->constrained('toy_stores');
                $table->bigInteger('total_amount')->comment('Total in kopecks');
                $table->string('status')->default('pending')->index();
                $table->string('payment_status')->default('unpaid')->index();
                $table->boolean('gift_requested')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('metadata')->nullable(); // Shipping details, gift note
                $table->timestamps();
                $table->comment('Order records with B2B/B2C logic separation.');
            });
        }

        if (!Schema::hasTable('toy_reviews')) {
            Schema::create('toy_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('toy_id')->constrained('toys')->onDelete('cascade');
                $table->unsignedBigInteger('user_id')->index();
                $table->integer('rating')->default(5);
                $table->text('comment')->nullable();
                $table->jsonb('metadata')->nullable(); // Verified purchase marker, child reaction
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Customer feedback with emotional reaction tracking.');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('toy_reviews');
        Schema::dropIfExists('toy_orders');
        Schema::dropIfExists('toy_subscription_boxes');
        Schema::dropIfExists('toys');
        Schema::dropIfExists('age_groups');
        // Keep categories and stores if required for shared use, but here we drop for clean dev
        Schema::dropIfExists('toy_categories');
        Schema::dropIfExists('toy_stores');
    }
};


