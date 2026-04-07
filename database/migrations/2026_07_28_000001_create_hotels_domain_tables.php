<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('hotels')) {
            Schema::create('hotels', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name');
                $table->text('description');
                $table->json('address');
                $table->json('amenities')->nullable();
                $table->float('rating')->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();

                $table->comment('Hotels belonging to tenants');
            });
        }

        if (!Schema::hasTable('hotel_rooms')) {
            Schema::create('hotel_rooms', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('hotel_id')->constrained('hotels')->onDelete('cascade');
                $table->string('type');
                $table->integer('price_per_night');
                $table->integer('capacity');
                $table->json('amenities')->nullable();
                $table->boolean('is_available')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();

                $table->comment('Rooms within a hotel');
            });
        }

        if (!Schema::hasTable('hotel_bookings')) {
            Schema::create('hotel_bookings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('hotel_id')->constrained('hotels')->onDelete('cascade');
                $table->foreignUuid('room_id')->constrained('hotel_rooms')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->timestamp('check_in_date');
                $table->timestamp('check_out_date');
                $table->integer('total_price');
                $table->string('status');
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();

                $table->index(['room_id', 'check_in_date', 'check_out_date']);
                $table->comment('Bookings for hotel rooms');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_bookings');
        Schema::dropIfExists('hotel_rooms');
        Schema::dropIfExists('hotels');
    }
};
