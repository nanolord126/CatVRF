<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stationery Vertical 2026 Schema.
 * Includes B2C/B2B support, gift wrapping, subscriptions, and AI-matching logs.
 * All tables MUST have tenant_id, uuid, and correlation_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stationery_stores')) {
            Schema::create('stationery_stores', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('city')->index();
                $table->decimal('rating', 3, 2)->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->jsonb('metadata')->nullable();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Stores for stationery and office supplies');
            });
        }

        if (!Schema::hasTable('stationery_categories')) {
            Schema::create('stationery_categories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name');
                $table->string('slug')->index();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                $table->comment('Hierarchical categories for stationery');
            });
        }

        if (!Schema::hasTable('stationery_products')) {
            Schema::create('stationery_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('store_id')->constrained('stationery_stores')->onDelete('cascade');
                $table->foreignId('category_id')->constrained('stationery_categories')->onDelete('cascade');
                $table->string('name')->index();
                $table->string('sku')->unique()->index();
                $table->unsignedBigInteger('price_cents')->comment('Retail/B2C price');
                $table->unsignedBigInteger('b2b_price_cents')->nullable()->comment('Bulk/Office price');
                $table->integer('stock_quantity')->default(0);
                $table->integer('min_stock_threshold')->default(10);
                $table->jsonb('attributes')->comment('Dimensions, material, brand, colors');
                $table->boolean('is_active')->default(true);
                $table->boolean('has_gift_wrapping')->default(false);
                $table->unsignedBigInteger('gift_wrap_price_cents')->default(0);
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Single stationery products');
            });
        }

        if (!Schema::hasTable('stationery_gift_sets')) {
            Schema::create('stationery_gift_sets', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('store_id')->constrained('stationery_stores')->onDelete('cascade');
                $table->string('name');
                $table->unsignedBigInteger('price_cents');
                $table->jsonb('product_ids')->comment('List of product IDs in the set');
                $table->string('theme')->index()->comment('E.g., Back to School, Office Start, Art Kit');
                $table->string('target_age_range')->nullable();
                $table->boolean('is_seasonal')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Predefined or custom stationery gift kits');
            });
        }

        if (!Schema::hasTable('stationery_subscriptions')) {
            Schema::create('stationery_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->index();
                $table->string('tier')->index(); // Basic, Premium, Office
                $table->unsignedBigInteger('monthly_price_cents');
                $table->boolean('is_active')->default(true);
                $table->timestamp('next_delivery_at')->nullable();
                $table->jsonb('preferences')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Monthly stationery boxes for enthusiasts or businesses');
            });
        }

        if (!Schema::hasTable('stationery_reviews')) {
            Schema::create('stationery_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->morphs('reviewable');
                $table->foreignId('user_id')->index();
                $table->integer('rating')->default(5);
                $table->text('comment')->nullable();
                $table->jsonb('photos')->nullable();
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                $table->comment('Polymorphic reviews for products and gift sets');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stationery_reviews');
        Schema::dropIfExists('stationery_subscriptions');
        Schema::dropIfExists('stationery_gift_sets');
        Schema::dropIfExists('stationery_products');
        Schema::dropIfExists('stationery_categories');
        Schema::dropIfExists('stationery_stores');
    }
};
