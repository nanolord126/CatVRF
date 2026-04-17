<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_analytics_daily', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->date('date');
            $table->integer('total_rides')->default(0);
            $table->integer('completed_rides')->default(0);
            $table->integer('cancelled_rides')->default(0);
            $table->integer('total_revenue_kopeki')->default(0);
            $table->float('total_distance_km')->default(0);
            $table->integer('total_duration_minutes')->default(0);
            $table->float('average_ride_distance_km')->default(0);
            $table->float('average_ride_duration_minutes')->default(0);
            $table->float('average_ride_price_rubles')->default(0);
            $table->float('surge_multiplier_avg')->default(1.0);
            $table->integer('active_drivers_count')->default(0);
            $table->integer('new_drivers_count')->default(0);
            $table->integer('active_passengers_count')->default(0);
            $table->integer('new_passengers_count')->default(0);
            $table->integer('peak_hour_rides')->default(0);
            $table->integer('peak_hour')->nullable();
            $table->integer('b2b_rides_count')->default(0);
            $table->integer('b2c_rides_count')->default(0);
            $table->integer('fleet_rides_count')->default(0);
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'date']);
            $table->index(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_analytics_daily');
    }
};
