<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('iot_device_id')->nullable()->constrained('iot_devices')->onDelete('cascade');
            
            $table->string('name');
            $table->enum('metric_type', [
                'temperature',
                'weight',
                'humidity',
                'door_state',
                'oil_level',
                'waste_level',
                'custom',
            ]);
            
            $table->enum('condition', ['greater_than', 'less_than', 'equals', 'not_equals', 'between']);
            $table->decimal('threshold_min', 20, 6)->nullable();
            $table->decimal('threshold_max', 20, 6)->nullable();
            $table->string('threshold_string')->nullable();
            
            $table->enum('severity', ['info', 'warning', 'critical', 'emergency']);
            $table->boolean('is_active')->default(true);
            
            $table->json('notification_config')->nullable(); // Who to notify
            $table->text('description')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'iot_device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_alert_rules');
    }
};
