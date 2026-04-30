<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cold_chain_readings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->decimal('temperature', 5, 2);
            $table->decimal('humidity', 5, 2)->nullable();
            $table->string('sensor_id', 100);
            $table->unsignedBigInteger('tenant_id');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['warehouse_id', 'zone_id']);
            $table->index('tenant_id');
            $table->index('recorded_at');
            $table->index('sensor_id');
        });

        Schema::create('cold_chain_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('zone_id');
            $table->string('alert_type'); // temperature, humidity
            $table->decimal('current_temperature', 5, 2);
            $table->decimal('current_humidity', 5, 2)->nullable();
            $table->json('violations');
            $table->string('status')->default('active'); // active, resolved, escalated
            $table->string('severity')->default('medium'); // low, medium, high, critical
            $table->boolean('notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->unsignedBigInteger('tenant_id');
            $table->timestamps();

            $table->index(['warehouse_id', 'zone_id', 'status']);
            $table->index(['tenant_id', 'status']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cold_chain_alerts');
        Schema::dropIfExists('cold_chain_readings');
    }
};
