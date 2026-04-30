<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_telemetry', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iot_device_id')->constrained('iot_devices')->onDelete('cascade');
            
            $table->string('metric_type'); // temperature, weight, door_state, etc.
            $table->decimal('value', 20, 6)->nullable();
            $table->string('value_string')->nullable(); // For non-numeric values
            $table->json('value_json')->nullable(); // For complex values
            
            $table->string('unit')->nullable(); // celsius, kg, etc.
            $table->boolean('is_alert')->default(false);
            $table->string('alert_message')->nullable();
            
            $table->timestamp('recorded_at');
            $table->timestamps();

            // Indexes for time-series queries
            $table->index(['iot_device_id', 'metric_type']);
            $table->index(['iot_device_id', 'recorded_at']);
            $table->index(['iot_device_id', 'is_alert']);
            $table->index('recorded_at');
        });

        // Create a partitioned table for high-volume telemetry (optional for ClickHouse)
        // For now, we'll use a regular MySQL table with proper indexing
        // In production, consider moving to ClickHouse for time-series data
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_telemetry');
    }
};
