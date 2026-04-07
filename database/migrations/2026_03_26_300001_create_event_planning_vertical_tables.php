<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create EventPlanning Vertical Tables.
 * Implementation: Layer 1 (Database Layer).
 * Includes: Planners, Events, Services, Venues, Packages, Bookings, Reviews.
 * Constraints: UTF-8, CRLF, comment, correlation_id, uuid, tenant_id.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Event Planners (Company or Private Freelancers)
        if (!Schema::hasTable('event_planners')) {
            Schema::create('event_planners', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name')->comment('Name of the agency or freelancer');
                $table->string('inn')->nullable()->index()->comment('INN for B2B/B2C identification');
                $table->string('type')->default('agency')->comment('agency, freelancer, hotel_partner');
                $table->decimal('rating', 3, 2)->default(5.00);
                $table->jsonb('specializations')->nullable()->comment('Wedding, Corporate, Kids, etc.');
                $table->jsonb('metadata')->nullable();
                $table->jsonb('tags')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Main entities for Event Planning vertical');
            });
        }

        // 2. Venues (Halls, Gardens, Restaurants for events)
        if (!Schema::hasTable('event_venues')) {
            Schema::create('event_venues', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('planner_id')->constrained('event_planners')->onDelete('cascade');
                $table->string('name');
                $table->string('address');
                $table->integer('capacity_max')->default(50);
                $table->integer('price_per_hour')->default(0)->comment('Price in kopecks');
                $table->jsonb('amenities')->nullable()->comment('WiFi, Stage, Catering, etc.');
                $table->jsonb('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Venues associated with event planning');
            });
        }

        // 3. Event Services (Decor, Hosting, Sound, Light)
        if (!Schema::hasTable('event_services')) {
            Schema::create('event_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('planner_id')->constrained('event_planners')->onDelete('cascade');
                $table->string('category')->index()->comment('decor, hosting, music, photography');
                $table->string('name');
                $table->text('description')->nullable();
                $table->integer('base_price')->default(0)->comment('Price in kopecks');
                $table->jsonb('options')->nullable();
                $table->jsonb('metadata')->nullable();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Individual services for events');
            });
        }

        // 4. Packages (Bundles of services: 'Standard Wedding', 'Corporate Light')
        if (!Schema::hasTable('event_packages')) {
            Schema::create('event_packages', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('planner_id')->constrained('event_planners')->onDelete('cascade');
                $table->string('name');
                $table->integer('total_price')->default(0);
                $table->integer('discount_percent')->default(0);
                $table->jsonb('service_ids')->comment('Array of service IDs included');
                $table->boolean('is_b2b_only')->default(false);
                $table->timestamps();
                $table->comment('Bundled event solutions');
            });
        }

        // 5. Events (The Projects themselves)
        if (!Schema::hasTable('events_projects')) {
            Schema::create('events_projects', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('planner_id')->constrained('event_planners');
                $table->foreignId('client_id')->index()->comment('User ID of the client');
                $table->string('title');
                $table->string('theme')->nullable();
                $table->dateTime('event_date');
                $table->integer('guest_count')->default(1);
                $table->string('status')->default('planning')->comment('planning, confirmed, active, completed, cancelled');
                $table->string('type')->default('b2c')->comment('b2b, b2c');
                $table->jsonb('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Actual event instances');
            });
        }

        // 6. Bookings (Financial transactions and assignments)
        if (!Schema::hasTable('event_bookings')) {
            Schema::create('event_bookings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('event_id')->constrained('events_projects')->onDelete('cascade');
                $table->foreignId('package_id')->nullable()->constrained('event_packages');
                $table->integer('total_amount')->default(0);
                $table->integer('prepayment_amount')->default(0);
                $table->string('payment_status')->default('unpaid')->comment('unpaid, partial, paid, refunded');
                $table->dateTime('expiry_at')->nullable()->comment('Deadline for prepayment');
                $table->jsonb('metadata')->nullable();
                $table->timestamps();
                $table->comment('Financial bookings for events');
            });
        }

        // 7. Reviews
        if (!Schema::hasTable('event_reviews')) {
            Schema::create('event_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('planner_id')->constrained('event_planners');
                $table->foreignId('client_id')->index();
                $table->integer('rating')->default(5);
                $table->text('comment')->nullable();
                $table->jsonb('media')->nullable()->comment('Photos from the event');
                $table->timestamps();
                $table->comment('Client feedback for event planning');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_reviews');
        Schema::dropIfExists('event_bookings');
        Schema::dropIfExists('events_projects');
        Schema::dropIfExists('event_packages');
        Schema::dropIfExists('event_services');
        Schema::dropIfExists('event_venues');
        Schema::dropIfExists('event_planners');
    }
};


