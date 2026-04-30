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
            
            $table->string('device_identifier', 100)->unique();
            $table->string('name', 255);
            $table->string('type', 50); // temperature_sensor, weight_scale, smart_timer, etc.
            $table->string('protocol', 20); // mqtt, websocket, modbus_tcp, modbus_rtu, http, coap
            
            $table->json('connection_config')->nullable();
            $table->string('broker_url')->nullable();
            $table->string('topic_prefix')->nullable();
            
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->json('metadata')->nullable();
            $table->text('description')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['tenant_id', 'is_active']);
            $table->index(['kitchen_station_id', 'is_online']);
            $table->index(['type', 'is_active']);
            $table->index(['protocol', 'is_online']);
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_devices');
    }
};
