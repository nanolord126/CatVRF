<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateLuxuryVerticalTables
 *
 * Layer 0: Database Layer
 * Создает полную структуру таблиц для вертикали Luxury (Премиум-класс).
 * Обязательно: UUID, correlation_id, tenant_id, scoping и высокая точность цен (копейки).
 *
 * @version 1.0.0
 * @author CatVRF
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Luxury Brands
        if (!Schema::hasTable('luxury_brands')) {
            Schema::create('luxury_brands', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->string('name')->comment('Brand Legal or Brand Name');
                $table->string('origin_country')->nullable()->comment('Country of origin (e.g. Italy, France)');
                $table->string('tier')->default('premium')->comment('luxury, ultra_luxury, bespoke');
                $table->string('website_url')->nullable();
                $table->jsonb('terms_json')->nullable()->comment('Custom B2B terms');
                $table->jsonb('tags')->nullable()->comment('Searchable tags (Bespoke, Heritage, eco)');
                $table->string('status')->default('active')->index();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Luxury brands participating in the marketplace');
            });
        }

        // 2. Luxury Products (Exclusive Items)
        if (!Schema::hasTable('luxury_products')) {
            Schema::create('luxury_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('brand_id')->constrained('luxury_brands')->onDelete('cascade');
                $table->string('sku')->unique()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->bigInteger('price_kopecks')->unsigned()->comment('Price in kopecks (RUB)');
                $table->bigInteger('min_deposit_kopecks')->unsigned()->default(0)->comment('Mandatory deposit amount');
                $table->jsonb('specifications')->nullable()->comment('Material, dimensions, craftsmanship details');
                $table->integer('current_stock')->default(0);
                $table->integer('hold_stock')->default(0);
                $table->boolean('is_personalized')->default(false)->comment('Can be customized for UI/UX');
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Exclusive luxury products (watches, jewelry, bags)');
            });
        }

        // 3. Luxury Services (Private Events, Limousine, Personal Shopping)
        if (!Schema::hasTable('luxury_services')) {
            Schema::create('luxury_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name');
                $table->string('category')->index(); // Concierge, PrivateJet, Yacht, Styling
                $table->bigInteger('base_price_kopecks')->unsigned();
                $table->integer('duration_minutes')->nullable();
                $table->jsonb('availability_json')->nullable()->comment('Operational hours/days');
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Luxury services for VIP clients');
            });
        }

        // 4. Exclusive Offers (Limited time / Invite only)
        if (!Schema::hasTable('luxury_exclusive_offers')) {
            Schema::create('luxury_exclusive_offers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('title');
                $table->text('content_json')->comment('Offer details and conditions');
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->integer('max_invites')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Invite-only exclusive offers for top-tier clients');
            });
        }

        // 5. Luxury Clients (VIP Profiles)
        if (!Schema::hasTable('luxury_clients')) {
            Schema::create('luxury_clients', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('vip_status')->default('silver')->index(); // Silver, Gold, Platinum, Black
                $table->bigInteger('total_spend_kopecks')->default(0);
                $table->jsonb('preferences_json')->nullable()->comment('Preferred categories, sizes, allergic info');
                $table->jsonb('lifestyle_profile')->nullable()->comment('Hobby, Travel frequency, Brand loyalty');
                $table->string('assigned_concierge_id')->nullable()->comment('Relation to Employee/Admin');
                $table->timestamp('last_vip_audit_at')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Extended VIP profiles for Luxury vertical clients');
            });
        }

        // 6. VIP Bookings (Personal experience booking)
        if (!Schema::hasTable('luxury_vip_bookings')) {
            Schema::create('luxury_vip_bookings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('client_id')->constrained('luxury_clients')->onDelete('cascade');
                $table->morphs('bookable'); // Product or Service
                $table->timestamp('scheduled_at')->nullable();
                $table->bigInteger('amount_total_kopecks')->unsigned();
                $table->bigInteger('amount_paid_kopecks')->default(0);
                $table->string('status')->default('pending')->index(); // pending, reserved, confirmed, fulfilled, cancelled
                $table->string('payment_status')->default('not_paid')->index();
                $table->jsonb('custom_requests')->nullable()->comment('Special flowers, drinks, security detail');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('VIP bookings for exclusive products and experiences');
            });
        }

        // 7. Luxury Reviews (Prestige Feedback)
        if (!Schema::hasTable('luxury_reviews')) {
            Schema::create('luxury_reviews', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('client_id')->constrained('luxury_clients')->onDelete('cascade');
                $table->morphs('reviewable');
                $table->integer('rating')->default(5)->comment('1-5 range');
                $table->text('comment')->nullable();
                $table->boolean('is_verified_purchase')->default(true);
                $table->jsonb('media')->nullable()->comment('Photos/Video of product/experience');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Verified prestigious feedback for luxury products/services');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('luxury_reviews');
        Schema::dropIfExists('luxury_vip_bookings');
        Schema::dropIfExists('luxury_clients');
        Schema::dropIfExists('luxury_exclusive_offers');
        Schema::dropIfExists('luxury_services');
        Schema::dropIfExists('luxury_products');
        Schema::dropIfExists('luxury_brands');
    }
};
