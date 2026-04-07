<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Collectibles Vertical Migration — CAÑON 2026.
 * Implements a high-precision schema for physical and digital collections.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Collectible Stores (Antique shops, Online dealers, Individual resellers)
        if (!Schema::hasTable('collectible_stores')) {
            Schema::create('collectible_stores', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name')->comment('Name of the shop or dealer');
                $table->string('address')->nullable();
                $table->text('description')->nullable();
                $table->decimal('rating', 3, 2)->default(0.00);
                $table->boolean('is_verified')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();

                $table->comment('Retail entities specializing in collectible items');
            });
        }

        // 2. Item Categories (Numismatics, Philately, Figures, Books, etc.)
        if (!Schema::hasTable('collectible_categories')) {
            Schema::create('collectible_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->timestamps();
            });
        }

        // 3. User Collections (Grouping of items bought or owned)
        if (!Schema::hasTable('user_collections')) {
            Schema::create('user_collections', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('name');
                $table->text('theme')->nullable()->comment('Subject of the collection');
                $table->timestamps();
            });
        }

        // 4. Collectible Items (The core entity: coins, cards, figures)
        if (!Schema::hasTable('collectible_items')) {
            Schema::create('collectible_items', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('store_id')->constrained('collectible_stores');
                $table->foreignId('category_id')->constrained('collectible_categories');
                $table->foreignId('collection_id')->nullable()->constrained('user_collections');
                $table->string('name');
                $table->text('description');
                $table->string('rarity')->comment('Common, Rare, Epic, Unique, Legendary');
                $table->string('condition_grade')->comment('Mint, Near Mint, Good, Used, Poor — PSA/BGS scale');
                $table->unsignedBigInteger('price_cents')->default(0);
                $table->unsignedBigInteger('estimated_value_cents')->nullable();
                $table->boolean('is_limited_edition')->default(false);
                $table->string('serial_number')->nullable()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('attributes')->nullable()->comment('Specific characteristics: year, material, series');
                $table->jsonb('tags')->nullable();
                $table->timestamps();

                $table->comment('The actual collectible objects for sale or archiving');
            });
        }

        // 5. Authenticity Certificates (Provenance records)
        if (!Schema::hasTable('collectible_certificates')) {
            Schema::create('collectible_certificates', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('item_id')->constrained('collectible_items');
                $table->string('certificate_number')->unique();
                $table->string('issuer')->comment('Grading service authority: PSA, SGC, CGC, NGC');
                $table->date('issued_at');
                $table->jsonb('report_data')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
            });
        }

        // 6. Auctions (Dynamic pricing engine)
        if (!Schema::hasTable('collectible_auctions')) {
            Schema::create('collectible_auctions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('item_id')->constrained('collectible_items');
                $table->unsignedBigInteger('start_price_cents');
                $table->unsignedBigInteger('reserve_price_cents')->nullable();
                $table->unsignedBigInteger('current_bid_cents')->default(0);
                $table->unsignedBigInteger('last_bidder_id')->nullable()->index();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->string('status')->default('scheduled')->comment('scheduled, active, completed, cancelled');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['status', 'ends_at']);
            });
        }

        // 7. Orders (Purchasing flow)
        if (!Schema::hasTable('collectible_orders')) {
            Schema::create('collectible_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->foreignId('item_id')->constrained('collectible_items');
                $table->unsignedBigInteger('total_cents');
                $table->string('status')->default('pending');
                $table->string('type')->default('b2c')->comment('b2c or b2b');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
            });
        }

        // 8. Reviews (Trust layer)
        if (!Schema::hasTable('collectible_reviews')) {
            Schema::create('collectible_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->morphs('reviewable');
                $table->integer('rating');
                $table->text('comment');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('collectible_reviews');
        Schema::dropIfExists('collectible_orders');
        Schema::dropIfExists('collectible_auctions');
        Schema::dropIfExists('collectible_certificates');
        Schema::dropIfExists('collectible_items');
        Schema::dropIfExists('user_collections');
        Schema::dropIfExists('collectible_categories');
        Schema::dropIfExists('collectible_stores');
    }
};


