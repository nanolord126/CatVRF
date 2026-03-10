<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hotels Module
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('number');
            $table->decimal('price', 15, 2);
            $table->string('status')->default('available'); // available, maintenance, occupied
            $table->boolean('requires_housekeeping')->default(false);
            $table->timestamps();
        });

        Schema::create('hotel_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained();
            $table->dateTime('check_in');
            $table->dateTime('check_out');
            $table->decimal('total_price', 15, 2);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        // BeautyMasters Module
        Schema::create('beauty_masters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->json('portfolio')->nullable(); // Images or Links
            $table->timestamps();
        });

        Schema::create('beauty_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 15, 2);
            $table->integer('duration_minutes');
            $table->timestamps();
        });

        Schema::create('master_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->constrained('beauty_masters');
            $table->foreignId('service_id')->constrained('beauty_services');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status')->default('booked');
            $table->timestamps();
        });

        // GeoLogistics & Delivery Module
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('center_lat', 10, 8);
            $table->decimal('center_lng', 11, 8);
            $table->float('radius_km'); // Circle radius calculation
            $table->decimal('base_delivery_price', 15, 2);
            $table->timestamps();
        });

        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->decimal('target_lat', 10, 8);
            $table->decimal('target_lng', 11, 8);
            $table->decimal('delivery_fee', 15, 2);
            $table->string('status')->default('pending'); // in_progress, delivered
            $table->string('tracking_number')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('delivery_zones');
        Schema::dropIfExists('master_appointments');
        Schema::dropIfExists('beauty_services');
        Schema::dropIfExists('beauty_masters');
        Schema::dropIfExists('hotel_bookings');
        Schema::dropIfExists('rooms');
    }
};
