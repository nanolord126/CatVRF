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
            $table->foreignId('iot_device_id')->nullable()->constrained('iot_devices')->onDelete('set null');
            
            $table->string('name', 255);
            $table->string('metric_type', 100);
            $table->string('condition', 50); // greater_than, less_than, equals, between
            $table->decimal('threshold_value', 10, 3)->nullable();
            $table->decimal('threshold_max', 10, 3)->nullable();
            
            $table->string('alert_level', 20)->default('warning'); // info, warning, critical
            $table->text('alert_message')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->boolean('notify_manager')->default(true);
            $table->boolean('block_operations')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['tenant_id', 'is_active']);
            $table->index(['iot_device_id', 'metric_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_alert_rules');
    }
};
