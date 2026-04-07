<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Beauty salons table
        Schema::dropIfExists('appointments');
        Schema::create('beauty_salons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->text('address');
            $table->string('geo_point', 255)->nullable();
            $table->json('schedule')->nullable();
            $table->float('rating')->default(0);
            $table->integer('review_count')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->index(['tenant_id']);
            $table->comment('Beauty salons');
        });

        // Masters table
        Schema::create('masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->nullable()->constrained('beauty_salons')->onDelete('cascade');
            $table->string('full_name');
            $table->json('specialization')->nullable();
            $table->integer('experience_years')->default(0);
            $table->float('rating')->default(0);
            $table->integer('review_count')->default(0);
            $table->timestamps();
            $table->index(['salon_id']);
            $table->comment('Beauty masters/stylists');
        });

        // Services table
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->nullable()->constrained('masters')->onDelete('cascade');
            $table->foreignId('salon_id')->nullable()->constrained('beauty_salons')->onDelete('cascade');
            $table->string('name');
            $table->integer('duration_minutes');
            $table->bigInteger('price')->comment('Price in kopeks');
            $table->json('consumables')->nullable();
            $table->timestamps();
            $table->index(['master_id', 'salon_id']);
            $table->comment('Beauty services');
        });

        // Appointments table
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('master_id')->constrained('masters')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->dateTime('datetime_start');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->bigInteger('price')->comment('Price in kopeks');
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->string('correlation_id')->index();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->comment('Beauty appointments');
        });

        // Restaurants table
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->text('address');
            $table->string('geo_point', 255)->nullable();
            $table->json('cuisine_type')->nullable();
            $table->json('schedule')->nullable();
            $table->float('rating')->default(0);
            $table->integer('review_count')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->index(['tenant_id']);
            $table->comment('Restaurants and cafes');
        });

        // Restaurant menus table
        Schema::create('restaurant_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['restaurant_id']);
            $table->comment('Restaurant menus');
        });

        // Dishes table
        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('restaurant_menus')->onDelete('cascade');
            $table->string('name');
            $table->bigInteger('price')->comment('Price in kopeks');
            $table->integer('calories')->nullable();
            $table->json('allergens')->nullable();
            $table->integer('cooking_time_minutes')->default(15);
            $table->json('consumables')->nullable();
            $table->timestamps();
            $table->index(['menu_id']);
            $table->comment('Dishes and food items');
        });

        // Restaurant orders table
        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('table_id')->nullable();
            $table->enum('status', ['pending', 'cooking', 'ready', 'delivered', 'cancelled'])->default('pending');
            $table->bigInteger('total_price')->comment('Total in kopeks');
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->json('items')->nullable();
            $table->text('address')->nullable();
            $table->string('correlation_id')->index();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->comment('Food orders');
        });

        // Hotels table
        Schema::dropIfExists('hotels');
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->text('address');
            $table->string('geo_point', 255)->nullable();
            $table->integer('stars')->default(3);
            $table->float('rating')->default(0);
            $table->integer('review_count')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->index(['tenant_id']);
            $table->comment('Hotels');
        });

        // Room types table
        Schema::dropIfExists('room_types');
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            $table->string('name');
            $table->integer('capacity');
            $table->bigInteger('price_per_night')->comment('Price in kopeks');
            $table->timestamps();
            $table->index(['hotel_id']);
            $table->comment('Hotel room types');
        });

        // Bookings table
        Schema::dropIfExists('bookings');
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('room_type_id')->constrained('room_types')->onDelete('cascade');
            $table->dateTime('check_in');
            $table->dateTime('check_out');
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'])->default('pending');
            $table->bigInteger('total_price')->comment('Total in kopeks');
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->string('correlation_id')->index();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->comment('Hotel bookings');
        });

        // Payout schedules table
        Schema::create('payout_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->dateTime('scheduled_for');
            $table->bigInteger('amount')->comment('Payout in kopeks');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->string('correlation_id')->index();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
            $table->comment('Hotel payout scheduling (4 days)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_schedules');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('hotels');
        Schema::dropIfExists('restaurant_orders');
        Schema::dropIfExists('dishes');
        Schema::dropIfExists('restaurant_menus');
        Schema::dropIfExists('restaurants');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('services');
        Schema::dropIfExists('masters');
        Schema::dropIfExists('beauty_salons');
    }
};


