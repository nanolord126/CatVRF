<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for PartySupplies vertical.
 * Contains Stores, Products, Categories, Themes, GiftSets, Orders, and Reviews.
 * Strictly follows CANON 2026: uuid, tenant_id, correlation_id, timestamps, and jsonb tags.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Party Stores
        if (!Schema::hasTable('party_stores')) {
            Schema::create('party_stores', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name')->comment('Store name: Balloons, Fireworks, Decor');
                $table->text('description')->nullable();
                $table->string('address')->nullable();
                $table->jsonb('contact_info')->nullable();
                $table->jsonb('metadata')->nullable()->comment('Delivery price, business hours, etc.');
                $table->decimal('rating', 3, 2)->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Central entity for PartySupplies retail and B2B stores.');
            });
        }

        // 2. Party Categories
        if (!Schema::hasTable('party_categories')) {
            Schema::create('party_categories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('party_store_id')->constrained('party_stores')->onDelete('cascade');
                $table->string('name')->index();
                $table->string('slug')->index();
                $table->text('description')->nullable();
                $table->string('icon_url')->nullable();
                $table->jsonb('metadata')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->comment('Nestable or flat categories for party products.');
            });
        }

        // 3. Party Themes
        if (!Schema::hasTable('party_themes')) {
            Schema::create('party_themes', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name')->comment('Wedding, Birthday, Corporate, Kids');
                $table->string('season')->nullable()->comment('Winter, Summer, Halloween, NY');
                $table->jsonb('color_palette')->nullable();
                $table->jsonb('metadata')->nullable();
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->comment('Themes for AI-assisted party planning.');
            });
        }

        // 4. Party Products
        if (!Schema::hasTable('party_products')) {
            Schema::create('party_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('party_store_id')->constrained('party_stores')->onDelete('cascade');
                $table->foreignId('category_id')->constrained('party_categories')->onDelete('cascade');
                $table->foreignId('theme_id')->nullable()->constrained('party_themes')->onDelete('set null');
                $table->string('name')->index();
                $table->string('sku')->unique()->index();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('price_cents')->comment('Retail price in cents');
                $table->unsignedBigInteger('b2b_price_cents')->nullable()->comment('Wholesale price for businesses');
                $table->integer('stock_quantity')->default(0);
                $table->integer('min_stock_threshold')->default(5);
                $table->boolean('has_gift_wrapping')->default(false);
                $table->unsignedBigInteger('gift_wrap_price_cents')->default(0);
                $table->jsonb('attributes')->nullable()->comment('Material, size, weight, flammable_status');
                $table->boolean('is_active')->default(true)->index();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Individual party items: Balloons, Candles, Hats, etc.');
            });
        }

        // 5. Party Gift Sets
        if (!Schema::hasTable('party_gift_sets')) {
            Schema::create('party_gift_sets', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('party_store_id')->constrained('party_stores')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('price_cents');
                $table->jsonb('product_ids')->comment('Array of linked product IDs');
                $table->string('theme_slug')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->comment('Bundled party kits (e.g. "Space Birthday Box").');
            });
        }

        // 6. Party Orders
        if (!Schema::hasTable('party_orders')) {
            Schema::create('party_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users');
                $table->foreignId('party_store_id')->constrained('party_stores');
                $table->string('status')->default('pending')->index()->comment('pending, authorized, confirmed, delivered, cancelled');
                $table->unsignedBigInteger('total_price_cents');
                $table->unsignedBigInteger('prepayment_cents')->default(0);
                $table->boolean('is_b2b')->default(false);
                $table->jsonb('items_json')->comment('Snapshot of ordered products');
                $table->string('delivery_address')->nullable();
                $table->timestamp('event_date')->nullable()->comment('Date of the party');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Orders for party supplies with optional prepayment.');
            });
        }

        // 7. Party Reviews
        if (!Schema::hasTable('party_reviews')) {
            Schema::create('party_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users');
                $table->morphs('reviewable');
                $table->unsignedTinyInteger('rating')->default(5);
                $table->text('comment')->nullable();
                $table->jsonb('media_urls')->nullable();
                $table->boolean('is_verified_purchase')->default(false);
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->comment('Polymorphic reviews for Party Stores or Products.');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('party_reviews');
        Schema::dropIfExists('party_orders');
        Schema::dropIfExists('party_gift_sets');
        Schema::dropIfExists('party_products');
        Schema::dropIfExists('party_themes');
        Schema::dropIfExists('party_categories');
        Schema::dropIfExists('party_stores');
    }
};
