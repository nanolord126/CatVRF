<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('str_reviews');
        Schema::dropIfExists('str_availability_calendars');
        Schema::dropIfExists('str_bookings');
        Schema::dropIfExists('short_term_rental_apartments');

        Schema::create('short_term_rental_apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->index();
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('address');
            $table->json('geo_point')->nullable();
            $table->integer('bedrooms');
            $table->integer('bathrooms');
            $table->integer('area_sqm');
            $table->integer('max_guests');
            $table->integer('base_price_per_night')->comment('in kopeks');
            $table->integer('cleaning_fee')->nullable()->comment('in kopeks');
            $table->float('rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            $table->boolean('is_verified')->default(false)->index();
            $table->integer('commission_rate')->default(1400)->comment('14%');
            $table->json('amenities')->nullable()->comment('["wifi", "parking", "washer"]');
            $table->boolean('is_active')->default(true)->index();
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'uuid']);
            $table->index(['tenant_id', 'is_active']);
            $table->comment('Short-term rental apartments/properties');
        });

        Schema::create('str_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index();
            $table->foreignId('apartment_id')->constrained('short_term_rental_apartments')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->date('check_in_date')->index();
            $table->date('check_out_date')->index();
            $table->integer('nights')->comment('calculated');
            $table->integer('guests_count');
            $table->integer('total_price')->comment('in kopeks');
            $table->integer('deposit_amount')->default(0)->comment('in kopeks, usually 10%');
            $table->integer('deposit_held')->default(0)->comment('held amount for damages/cleaning');
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'])->default('pending')->index();
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending')->index();
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->dateTime('hold_until')->nullable()->comment('20-min hold');
            $table->text('guest_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'check_in_date']);
            $table->index(['apartment_id', 'check_in_date', 'check_out_date']);
            $table->comment('Short-term rental bookings');
        });

        Schema::create('str_availability_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained('short_term_rental_apartments')->onDelete('cascade')->index();
            $table->date('date')->index();
            $table->boolean('is_available')->default(true);
            $table->integer('price_override')->nullable();
            $table->string('reason')->nullable()->comment('blocked, maintenance, owner trip');
            $table->timestamps();

            $table->unique(['apartment_id', 'date']);
            $table->comment('Calendar availability and pricing per date');
        });

        Schema::create('str_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index();
            $table->foreignId('apartment_id')->constrained('short_term_rental_apartments')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->integer('rating')->comment('1-5');
            $table->text('comment');
            $table->json('photos')->nullable();
            $table->integer('cleanliness')->nullable();
            $table->integer('location')->nullable();
            $table->integer('communication')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['apartment_id', 'created_at']);
            $table->comment('STR reviews');
        });

        Schema::create('str_cleaning_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained('short_term_rental_apartments')->onDelete('cascade')->index();
            $table->foreignId('booking_id')->nullable()->constrained('str_bookings')->onDelete('set null')->index();
            $table->uuid('uuid')->unique()->index();
            $table->date('cleaning_date')->index();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'failed'])->default('scheduled')->index();
            $table->text('notes')->nullable();
            $table->string('assigned_to')->nullable()->comment('cleaner name/ID');
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['apartment_id', 'cleaning_date']);
            $table->comment('Cleaning schedules between bookings');
        });

        Schema::create('str_smart_lock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained('short_term_rental_apartments')->onDelete('cascade')->index();
            $table->foreignId('booking_id')->nullable()->constrained('str_bookings')->onDelete('set null')->index();
            $table->uuid('uuid')->unique()->index();
            $table->dateTime('event_time')->index();
            $table->enum('action', ['unlock', 'lock', 'code_generated', 'access_denied'])->index();
            $table->string('guest_phone')->nullable();
            $table->text('details')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['apartment_id', 'event_time']);
            $table->comment('Smart lock access logs for security');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('str_smart_lock_logs');
        Schema::dropIfExists('str_cleaning_schedules');
        Schema::dropIfExists('str_reviews');
        Schema::dropIfExists('str_availability_calendars');
        Schema::dropIfExists('str_bookings');
        Schema::dropIfExists('short_term_rental_apartments');
    }
};


