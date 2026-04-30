<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_temperature_monitoring_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            
            // Device identification
            $table->string('device_id')->unique()->comment('Unique device identifier from manufacturer');
            $table->string('name')->comment('Human-readable device name');
            $table->string('serial_number')->nullable()->unique();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            
            // Access credentials
            $table->string('access_key')->unique()->comment('API access key for device communication');
            $table->string('access_secret')->nullable()->comment('Encrypted secret for device authentication');
            $table->string('mqtt_topic')->nullable()->comment('MQTT topic for device telemetry');
            
            // Location
            $table->string('location')->nullable()->comment('Physical location (e.g., Warehouse A, Freezer 3)');
            $table->string('zone')->nullable()->comment('Storage zone identifier');
            $table->json('coordinates')->nullable()->comment('GPS coordinates or internal positioning');
            
            // Device configuration
            $table->integer('reporting_interval_seconds')->default(300)->comment('How often device reports temperature');
            $table->decimal('accuracy_celsius', 5, 2)->default(0.5)->comment('Temperature accuracy in Celsius');
            
            // Status
            $table->enum('status', ['active', 'inactive', 'maintenance', 'offline', 'decommissioned'])->default('active');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_calibration_at')->nullable();
            $table->timestamp('next_calibration_due_at')->nullable();
            
            // Metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable()->comment('Additional device-specific data');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index('device_id');
            $table->index('location');
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_temperature_monitoring_devices');
    }
};
