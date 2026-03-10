<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. ОТЕЛИ (PMS - Property Management System)
        Schema::create('hotel_room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('base_price', 12, 2);
            $table->integer('capacity');
            $table->json('amenities')->nullable(); // Wi-Fi, AC, TV, Mini-bar
            $table->timestamps();
        });

        Schema::create('hotel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained('hotel_room_types')->onDelete('cascade');
            $table->string('room_number');
            $table->string('floor')->nullable();
            $table->enum('status', ['available', 'occupied', 'cleaning', 'maintenance'])->default('available');
            $table->timestamps();
        });

        Schema::create('hotel_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->foreignId('room_id')->constrained('hotel_rooms')->onDelete('cascade');
            $table->timestamp('check_in');
            $table->timestamp('check_out');
            $table->decimal('total_price', 12, 2);
            $table->enum('status', ['confirmed', 'checked_in', 'checked_out', 'cancelled'])->default('confirmed');
            $table->string('correlation_id')->index();
            $table->timestamps();
        });

        // 2. РЕСТОРАНЫ (POS - Point of Sale)
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->string('table_number');
            $table->integer('capacity');
            $table->string('location_tag')->nullable(); // Terrace, VIP Room
            $table->enum('status', ['free', 'reserved', 'occupied'])->default('free');
            $table->timestamps();
        });

        Schema::create('restaurant_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('restaurant_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('restaurant_categories')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->nullable()->constrained('restaurant_tables');
            $table->unsignedBigInteger('waiter_id')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('status', ['pending', 'preparing', 'ready', 'served', 'paid', 'cancelled'])->default('pending');
            $table->string('correlation_id')->index();
            $table->timestamps();
        });

        Schema::create('restaurant_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('restaurant_orders')->onDelete('cascade');
            $table->foreignId('menu_item_id')->constrained('restaurant_menu_items');
            $table->integer('quantity');
            $table->decimal('price', 12, 2); // Price at time of order
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. КЛИНИКИ (HIS - Hospital Information System)
        Schema::create('clinic_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->integer('duration_minutes')->default(30);
            $table->timestamps();
        });

        Schema::create('clinic_appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->foreignId('service_id')->constrained('clinic_services');
            $table->timestamp('appointment_at');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->string('correlation_id')->index();
            $table->timestamps();
        });

        Schema::create('clinic_medical_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->foreignId('appointment_id')->nullable()->constrained('clinic_appointments');
            $table->text('diagnosis');
            $table->text('treatment_plan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_medical_records');
        Schema::dropIfExists('clinic_appointments');
        Schema::dropIfExists('clinic_services');
        Schema::dropIfExists('restaurant_order_items');
        Schema::dropIfExists('restaurant_orders');
        Schema::dropIfExists('restaurant_menu_items');
        Schema::dropIfExists('restaurant_categories');
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('hotel_bookings');
        Schema::dropIfExists('hotel_rooms');
        Schema::dropIfExists('hotel_room_types');
    }
};
