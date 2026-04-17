<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_driver_analytics', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('driver_id');
            $table->date('date');
            $table->integer('total_rides')->default(0);
            $table->integer('completed_rides')->default(0);
            $table->integer('cancelled_rides')->default(0);
            $table->integer('total_revenue_kopeki')->default(0);
            $table->float('total_distance_km')->default(0);
            $table->integer('total_duration_minutes')->default(0);
            $table->integer('online_minutes')->default(0);
            $table->float('acceptance_rate')->default(0);
            $table->float('cancellation_rate')->default(0);
            $table->float('average_rating')->default(5.0);
            $table->integer('total_tips_kopeki')->default(0);
            $table->integer('bonus_kopeki')->default(0);
            $table->integer('penalty_kopeki')->default(0);
            $table->float('surge_multiplier_avg')->default(1.0);
            $table->integer('peak_hours_rides')->default(0);
            $table->integer('peak_hours_revenue_kopeki')->default(0);
            $table->integer('b2b_rides_count')->default(0);
            $table->integer('b2b_revenue_kopeki')->default(0);
            $table->integer('average_response_time_seconds')->default(0);
            $table->float('average_pickup_time_minutes')->default(0);
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'driver_id', 'date']);
            $table->index(['tenant_id', 'driver_id']);
            $table->index(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_driver_analytics');
    }
};
