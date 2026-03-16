<?php

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
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('stars')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();

            $table->string('correlation_id')->nullable()->index();        });

        Schema::create('beauty_salons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();

            $table->string('correlation_id')->nullable()->index();        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner'); // Hotel or Salon
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->timestamps();

            $table->string('correlation_id')->nullable()->index();        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->morphs('bookable'); // Hotel or Salon
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('status')->default('pending'); // pending, confirmed, cancelled, completed
            $table->decimal('total_price', 15, 2);
            $table->timestamps();

            $table->string('correlation_id')->nullable()->index();        });

        Schema::create('geo_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // radius, polygon
            $table->json('coordinates'); // center [lat, lng] + radius or coordinates array
            $table->timestamps();

            $table->string('correlation_id')->nullable()->index();        });

        Schema::create('geo_events', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // visit, order
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->unsignedBigInteger('intensity')->default(1);
            $table->timestamps();

            $table->string('correlation_id')->nullable()->index();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('beauty_salons');
        Schema::dropIfExists('hotels');
        Schema::dropIfExists('geo_zones');
        Schema::dropIfExists('geo_events');
    }
};

