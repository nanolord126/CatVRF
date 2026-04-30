<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('kitchen_station_id')->nullable()->constrained('kitchen_stations')->onDelete('set null');
            
            $table->string('device_identifier')->unique(); // MAC address, serial number, or unique ID
            $table->string('name');
            $table->enum('type', [
                'temperature_sensor',
                'weight_scale',
                'smart_timer',
                'door_sensor',
                'smart_printer',
                'kds_display',
                'smart_stove',
                'camera_vision',
                'smart_lock',
                'oil_level_sensor',
                'waste_bin_sensor',
                'humidity_sensor',
                'generic',
            ]);
            
            $table->enum('protocol', ['mqtt', 'websocket', 'modbus_tcp', 'modbus_rtu', 'http', 'coap']);
            $table->string('connection_config')->nullable(); // JSON config for connection
            $table->string('broker_url')->nullable(); // MQTT broker URL
            $table->string('topic_prefix')->nullable(); // MQTT topic prefix
            
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->json('metadata')->nullable(); // Device-specific metadata
            $table->text('description')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'kitchen_station_id']);
            $table->index(['tenant_id', 'is_online']);
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_devices');
    }
};
