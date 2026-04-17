<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_driver_stats', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('driver_id')->constrained('taxi_drivers')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('rides_started')->default(0);
            $table->integer('rides_completed')->default(0);
            $table->integer('rides_cancelled')->default(0);
            $table->integer('total_earnings')->default(0);
            $table->decimal('total_distance_km', 10, 2)->default(0);
            $table->integer('total_time_minutes')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('current_streak')->default(0);
            $table->integer('max_streak')->default(0);
            $table->timestamp('last_ride_at')->nullable();
            $table->decimal('online_hours_today', 5, 2)->default(0);
            $table->decimal('online_hours_week', 5, 2)->default(0);
            $table->integer('rides_today')->default(0);
            $table->integer('rides_week')->default(0);
            $table->integer('earnings_today')->default(0);
            $table->integer('earnings_week')->default(0);
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 100)->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'tenant_id']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_driver_stats');
    }
};
