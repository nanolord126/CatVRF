<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_vehicle_inspections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('fleet_id')->nullable();
            $table->string('type');
            $table->string('status')->default('scheduled');
            $table->date('inspection_date');
            $table->date('expiry_date')->nullable();
            $table->string('inspector_name')->nullable();
            $table->string('inspector_license')->nullable();
            $table->string('result')->nullable();
            $table->integer('defects_found')->default(0);
            $table->integer('defects_fixed')->default(0);
            $table->json('documents')->nullable();
            $table->date('next_inspection_date')->nullable();
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'vehicle_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_vehicle_inspections');
    }
};
