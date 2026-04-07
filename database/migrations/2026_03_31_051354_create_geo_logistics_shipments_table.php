<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('geo_shipments');
        Schema::create('geo_shipments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('delivery_order_id')->nullable()->index();
            $table->unsignedBigInteger('courier_id')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->decimal('pickup_lat', 10, 8)->nullable();
            $table->decimal('pickup_lng', 11, 8)->nullable();
            $table->decimal('dropoff_lat', 10, 8)->nullable();
            $table->decimal('dropoff_lng', 11, 8)->nullable();
            $table->decimal('current_lat', 10, 8)->nullable();
            $table->decimal('current_lng', 11, 8)->nullable();
            $table->integer('estimated_distance_meters')->nullable();
            $table->integer('estimated_duration_seconds')->nullable();
            $table->integer('calculated_cost')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->jsonb('tags')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_shipments');
    }
};



