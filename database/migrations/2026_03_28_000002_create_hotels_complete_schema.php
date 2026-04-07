<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hotels')) {
            return;
        }

        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->index();
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address');
            $table->geometry('geo_point')->nullable()->spatialIndex();
            $table->integer('stars')->default(3)->comment('1-5');
            $table->float('rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            $table->boolean('is_verified')->default(false)->index();
            $table->integer('commission_rate')->default(1400)->comment('in kopeks, 14%');
            $table->json('amenities')->nullable()->comment('["wifi", "parking", "pool", ...]');
            $table->json('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'uuid']);
            $table->index(['tenant_id', 'stars']);
            $table->comment('Hotels with amenities and star ratings');
        });

        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name')->comment('Single, Double, Suite, etc.');
            $table->integer('capacity')->comment('max guests');
            $table->integer('area_sqm')->nullable();
            $table->integer('base_price')->comment('in kopeks per night');
            $table->json('amenities')->nullable()->comment('["AC", "TV", "minibar"]');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['hotel_id', 'capacity']);
            $table->comment('Types of rooms at hotel (Single, Double, Suite)');
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained('room_types')->onDelete('cascade')->index();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('room_number')->unique();
            $table->integer('floor')->nullable();
            $table->enum('status', ['available', 'occupied', 'maintenance', 'dirty'])->default('available')->index();
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['hotel_id', 'room_number']);
            $table->index(['hotel_id', 'status']);
            $table->comment('Individual rooms at hotel');
        });

        Schema::create('hotel_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade')->index();
            $table->foreignId('room_type_id')->constrained('room_types')->onDelete('cascade')->index();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->onDelete('set null')->index();
            $table->uuid('uuid')->unique()->index();
            $table->date('check_in_date')->index();
            $table->date('check_out_date')->index();
            $table->integer('nights')->comment('calculated field');
            $table->integer('guests_count');
            $table->integer('total_price')->comment('in kopeks');
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'])->default('pending')->index();
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending')->index();
            $table->integer('deposit_amount')->default(0)->comment('in kopeks');
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->dateTime('hold_until')->nullable()->comment('20-min hold expiry');
            $table->text('special_requests')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'check_in_date']);
            $table->index(['hotel_id', 'check_in_date', 'check_out_date']);
            $table->comment('Hotel bookings with hold logic for 20-min reserve');
        });

        Schema::create('room_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained('room_types')->onDelete('cascade')->index();
            $table->date('date')->index();
            $table->integer('total_rooms')->comment('total rooms of this type');
            $table->integer('available_rooms')->comment('not booked');
            $table->integer('price_override')->nullable()->comment('dynamic pricing');
            $table->timestamps();

            $table->unique(['room_type_id', 'date']);
            $table->index(['date']);
            $table->comment('Room availability and pricing per date');
        });

        Schema::create('hotel_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->integer('rating')->comment('1-5');
            $table->text('comment');
            $table->json('photos')->nullable();
            $table->integer('cleanliness')->nullable();
            $table->integer('comfort')->nullable();
            $table->integer('service')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['hotel_id', 'created_at']);
            $table->comment('Hotel reviews');
        });

        Schema::create('payout_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade')->index();
            $table->foreignId('booking_id')->constrained('hotel_bookings')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->integer('amount')->comment('in kopeks, net after commission');
            $table->enum('status', ['pending', 'scheduled', 'processed', 'failed'])->default('pending')->index();
            $table->date('payout_date')->nullable()->comment('4 days after checkout');
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['hotel_id', 'payout_date']);
            $table->index(['status', 'payout_date']);
            $table->comment('Payout schedule: 4 days after checkout');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_schedules');
        Schema::dropIfExists('hotel_reviews');
        Schema::dropIfExists('room_availability');
        Schema::dropIfExists('hotel_bookings');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('hotels');
    }
};


