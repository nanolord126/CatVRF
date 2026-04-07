<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create CarRental vertical Tables.
 * 2026 Canonical Style: UUID, Tenant Scoping, JSONB, Correlation_id, Audit Comments.
 * Implementation: Layers 1 (Database Layer).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rental Companies
        if (!Schema::hasTable('rental_companies')) {
            Schema::create('rental_companies', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name')->comment('Company brand name');
                $table->string('inn', 12)->nullable()->index()->comment('Legal Tax ID');
                $table->boolean('is_verified')->default(false)->index();
                $table->decimal('rating', 3, 2)->default(5.00);
                $table->jsonb('settings')->nullable()->comment('Work schedule, deposit rules, commission');
                $table->jsonb('tags')->nullable()->comment('Eco-friendly, 24/7, meet-and-greet');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Fleet owners and car rental platforms');
            });
        }

        // 2. Vehicle Types (Classes)
        if (!Schema::hasTable('car_types')) {
            Schema::create('car_types', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name')->comment('Economy, Business, SUV, Truck');
                $table->integer('daily_price_base')->comment('Base price in kopecks');
                $table->integer('seats')->default(5);
                $table->integer('baggage_capacity')->default(2);
                $table->jsonb('features')->nullable()->comment('AC, GPS, Automatic, Child Seat');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Vehicle categorization for pricing and selection');
            });
        }

        // 3. Cars (Fleet)
        if (!Schema::hasTable('cars')) {
            Schema::create('cars', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('rental_company_id')->constrained('rental_companies')->onDelete('cascade');
                $table->foreignId('car_type_id')->constrained('car_types');
                $table->string('brand')->index();
                $table->string('model')->index();
                $table->string('plate_number')->unique()->index();
                $table->integer('mileage')->default(0);
                $table->enum('status', ['available', 'rented', 'maintenance', 'reserved', 'sold'])->default('available')->index();
                $table->jsonb('attributes')->nullable()->comment('Color, fuel_type, engine_volume');
                $table->jsonb('media')->nullable()->comment('Photos of car exterior/interior');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Individual vehicle units in the fleet');
            });
        }

        // 4. Insurances
        if (!Schema::hasTable('car_insurances')) {
            Schema::create('car_insurances', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name')->comment('Full Coverage, CDW, TPL');
                $table->integer('daily_cost')->comment('Cost per day in kopecks');
                $table->integer('deductible')->comment('Franchise amount in kopecks');
                $table->text('description')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Add-on insurance plans for rentals');
            });
        }

        // 5. Rental Bookings (Main Transaction)
        if (!Schema::hasTable('car_bookings')) {
            Schema::create('car_bookings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->index();
                $table->foreignId('car_id')->constrained('cars');
                $table->foreignId('insurance_id')->nullable()->constrained('car_insurances');
                
                $table->dateTime('starts_at')->index();
                $table->dateTime('ends_at')->index();
                
                $table->integer('daily_price')->comment('Snapshot of price at booking');
                $table->integer('total_price')->comment('Final calculated price');
                $table->integer('deposit_amount')->comment('Hold amount for car');
                
                $table->enum('status', ['pending', 'confirmed', 'picked_up', 'returned', 'cancelled', 'disputed'])->default('pending')->index();
                $table->boolean('is_b2b')->default(false)->index();
                $table->string('firm_name')->nullable()->comment('For B2B rentals');
                
                $table->jsonb('check_in_data')->nullable()->comment('Photos and condition during pickup');
                $table->jsonb('check_out_data')->nullable()->comment('Photos and condition during return');
                
                $table->string('idempotency_key')->nullable()->unique();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Primary rental transactions');
            });
        }

        // 6. Rental Reviews
        if (!Schema::hasTable('car_reviews')) {
            Schema::create('car_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->index();
                $table->foreignId('booking_id')->constrained('car_bookings');
                $table->foreignId('car_id')->constrained('cars');
                $table->integer('rating')->default(5);
                $table->text('comment')->nullable();
                $table->jsonb('media')->nullable()->comment('Review photos');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Customer feedback on car and provider');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_reviews');
        Schema::dropIfExists('car_bookings');
        Schema::dropIfExists('car_insurances');
        Schema::dropIfExists('cars');
        Schema::dropIfExists('car_types');
        Schema::dropIfExists('rental_companies');
    }
};


