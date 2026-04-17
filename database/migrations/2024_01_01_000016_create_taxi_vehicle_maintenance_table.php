<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_vehicle_maintenance', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('fleet_id')->nullable();
            $table->string('type');
            $table->text('description')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamp('scheduled_date');
            $table->timestamp('completed_date')->nullable();
            $table->integer('cost_kopeki')->default(0);
            $table->string('performed_by')->nullable();
            $table->integer('odometer_km')->default(0);
            $table->timestamp('next_maintenance_date')->nullable();
            $table->integer('next_maintenance_odometer_km')->nullable();
            $table->json('documents')->nullable();
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'vehicle_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_vehicle_maintenance');
    }
};
