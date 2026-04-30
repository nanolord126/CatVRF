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
            
            $table->string('metric_type', 100); // temperature, weight, humidity, etc.
            
            // Value storage - different types for different metrics
            $table->decimal('value', 10, 3)->nullable();
            $table->string('value_string')->nullable();
            $table->json('value_json')->nullable();
            
            $table->string('unit', 20)->nullable(); // celsius, kg, %, etc.
            
            $table->boolean('is_alert')->default(false);
            $table->string('alert_message')->nullable();
            $table->string('alert_level', 20)->nullable(); // info, warning, critical
            
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
            
            // Indexes for performance and queries
            $table->index(['iot_device_id', 'metric_type']);
            $table->index(['iot_device_id', 'recorded_at']);
            $table->index(['metric_type', 'recorded_at']);
            $table->index(['is_alert', 'recorded_at']);
            $table->index('recorded_at');
        });

        // Create a partitioned table approach for ClickHouse integration
        // For large-scale telemetry data, we'll use ClickHouse
        // This is a placeholder for ClickHouse migration
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_telemetry');
    }
};
