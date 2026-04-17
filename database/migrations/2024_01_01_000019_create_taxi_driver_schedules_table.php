<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_driver_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('driver_id');
            $table->date('date');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->string('status')->default('scheduled');
            $table->timestamp('break_start_time')->nullable();
            $table->timestamp('break_end_time')->nullable();
            $table->integer('target_rides')->default(10);
            $table->integer('target_earnings_kopeki')->default(500000);
            $table->integer('actual_rides')->default(0);
            $table->integer('actual_earnings_kopeki')->default(0);
            $table->integer('online_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'driver_id']);
            $table->index(['tenant_id', 'date']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_driver_schedules');
    }
};
