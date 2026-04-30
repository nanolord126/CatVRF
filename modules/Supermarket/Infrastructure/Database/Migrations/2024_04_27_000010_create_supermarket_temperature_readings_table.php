<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_temperature_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('supermarket_temperature_monitoring_devices')->onDelete('cascade');
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            
            // Temperature data
            $table->decimal('temperature_celsius', 5, 2)->comment('Temperature reading in Celsius');
            $table->decimal('humidity_percent', 5, 2)->nullable()->comment('Humidity percentage if available');
            
            // Reading metadata
            $table->timestamp('recorded_at')->comment('When the temperature was recorded by device');
            $table->timestamp('received_at')->comment('When the reading was received by system');
            $table->string('correlation_id')->nullable()->comment('Correlation ID for traceability');
            
            // Violation detection
            $table->boolean('is_violation')->default(false);
            $table->enum('violation_severity', ['none', 'warning', 'critical'])->default('none');
            $table->foreignId('product_requirement_id')->nullable()->constrained('supermarket_product_temperature_requirements')->onDelete('set null');
            
            // Alert status
            $table->boolean('alert_sent')->default(false);
            $table->timestamp('alert_sent_at')->nullable();
            
            // Raw data
            $table->json('raw_data')->nullable()->comment('Raw sensor data');
            
            $table->timestamps();
            
            // Indexes for queries
            $table->index(['device_id', 'recorded_at']);
            $table->index(['tenant_id', 'recorded_at']);
            $table->index('is_violation');
            $table->index('violation_severity');
            $table->index('alert_sent');
            $table->index('recorded_at');
            
            // Partitioning hint for large datasets
            //$table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_temperature_readings');
    }
};
