<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for CleaningServices Vertical (2026 Canonical).
 * Implements 7 tables for comprehensive cleaning management.
 * Includes B2B/B2C support, Geo-points, and JSONB attributes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Cleaning Companies (The Service Providers)
        if (!Schema::hasTable('cleaning_companies')) {
            Schema::create('cleaning_companies', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name')->comment('Legal or brand name of the company');
                $table->string('inn', 12)->nullable()->comment('INN for B2B contracts');
                $table->enum('type', ['local', 'aggregator', 'premium', 'industrial'])->default('local');
                $table->decimal('rating', 3, 2)->default(5.00);
                $table->jsonb('settings')->nullable()->comment('Operational settings, commissions, work hours');
                $table->jsonb('tags')->nullable()->comment('Analytics tags: eco-friendly, fast, deep-clean');
                $table->boolean('is_verified')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Main entities for cleaning service providers within a tenant');
            });
        }

        // 2. Cleaning Services (The Products)
        if (!Schema::hasTable('cleaning_services')) {
            Schema::create('cleaning_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('cleaning_company_id')->constrained('cleaning_companies')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('category', ['standard', 'general', 'post_construction', 'window', 'dry_cleaning', 'office']);
                $table->integer('price_base_cents')->default(0)->comment('Base price per unit (sqm or hour)');
                $table->string('unit')->default('sqm')->comment('sqm, hour, item');
                $table->integer('estimated_duration_minutes')->default(60);
                $table->jsonb('consumables_required')->nullable()->comment('List of chemicals and equipment needed');
                $table->boolean('is_active')->default(true)->index();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Definitions of cleaning services offered by companies');
            });
        }

        // 3. Cleaning Addresses (Service Locations)
        if (!Schema::hasTable('cleaning_addresses')) {
            Schema::create('cleaning_addresses', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->index();
                $table->string('address_line');
                $table->decimal('lat', 10, 8)->nullable();
                $table->decimal('lon', 11, 8)->nullable();
                $table->string('access_info')->nullable()->comment('Intercom code, key location, security instructions');
                $table->enum('property_type', ['apartment', 'house', 'office', 'warehouse', 'commercial']);
                $table->decimal('area_sqm', 8, 2)->nullable();
                $table->jsonb('metadata')->nullable()->comment('Photo of the entrance, specific room info');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Stored locations for recurring or one-time cleaning orders');
            });
        }

        // 4. Cleaning Orders (The Core Transactions)
        if (!Schema::hasTable('cleaning_orders')) {
            Schema::create('cleaning_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->index();
                $table->foreignId('cleaning_company_id')->constrained('cleaning_companies');
                $table->foreignId('cleaning_service_id')->constrained('cleaning_services');
                $table->foreignId('cleaning_address_id')->constrained('cleaning_addresses');
                
                $table->enum('status', ['pending', 'confirmed', 'in_progress', 'inspected', 'completed', 'cancelled'])->default('pending')->index();
                $table->timestamp('scheduled_at')->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                
                $table->integer('total_cents')->default(0);
                $table->integer('prepayment_cents')->default(0);
                $table->enum('payment_status', ['unpaid', 'authorized', 'captured', 'refunded'])->default('unpaid');
                
                $table->jsonb('photos_before')->nullable();
                $table->jsonb('photos_after')->nullable();
                $table->text('client_wishes')->nullable();
                $table->jsonb('inspection_data')->nullable()->comment('QA checklist results');
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->comment('Master table for cleaning service requests');
            });
        }

        // 5. Cleaning Schedules (Staff Allocation)
        if (!Schema::hasTable('cleaning_schedules')) {
            Schema::create('cleaning_schedules', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('cleaning_company_id')->constrained('cleaning_companies');
                $table->foreignId('cleaner_id')->index()->comment('User ID of the staff member');
                $table->foreignId('cleaning_order_id')->nullable()->constrained('cleaning_orders');
                
                $table->timestamp('start_time')->index();
                $table->timestamp('end_time')->index();
                $table->enum('type', ['work', 'break', 'task', 'unavailable'])->default('task');
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Staff work schedules and task assignments');
            });
        }

        // 6. Cleaning Consumables (Inventory)
        if (!Schema::hasTable('cleaning_consumables')) {
            Schema::create('cleaning_consumables', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('cleaning_company_id')->constrained('cleaning_companies');
                
                $table->string('name');
                $table->string('sku')->nullable()->index();
                $table->integer('stock_quantity')->default(0);
                $table->integer('min_threshold')->default(10);
                $table->string('unit')->default('pcs')->comment('pcs, ml, gram, pack');
                
                $table->jsonb('safety_data')->nullable()->comment('SDS links or chemical risk info');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Chemicals, tools, and supplies inventory for cleaning companies');
            });
        }

        // 7. Cleaning Reviews
        if (!Schema::hasTable('cleaning_reviews')) {
            Schema::create('cleaning_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->index();
                $table->foreignId('cleaning_order_id')->unique()->constrained('cleaning_orders');
                
                $table->tinyInteger('rating_purity')->unsigned()->default(5);
                $table->tinyInteger('rating_punctuality')->unsigned()->default(5);
                $table->tinyInteger('rating_politeness')->unsigned()->default(5);
                $table->text('comment')->nullable();
                $table->jsonb('review_photos')->nullable();
                
                $table->boolean('is_public')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('QA and customer satisfaction reviews for cleaning jobs');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleaning_reviews');
        Schema::dropIfExists('cleaning_consumables');
        Schema::dropIfExists('cleaning_schedules');
        Schema::dropIfExists('cleaning_orders');
        Schema::dropIfExists('cleaning_addresses');
        Schema::dropIfExists('cleaning_services');
        Schema::dropIfExists('cleaning_companies');
    }
};
