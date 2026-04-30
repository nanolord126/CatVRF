<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_product_temperature_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            
            // Temperature range requirements
            $table->decimal('min_temperature_celsius', 5, 2)->nullable();
            $table->decimal('max_temperature_celsius', 5, 2)->nullable();
            $table->decimal('optimal_temperature_celsius', 5, 2)->nullable();
            
            // Storage type
            $table->enum('storage_type', ['frozen', 'refrigerated', 'ambient', 'heated'])->default('ambient');
            
            // Critical thresholds
            $table->decimal('critical_min_temperature_celsius', 5, 2)->nullable();
            $table->decimal('critical_max_temperature_celsius', 5, 2)->nullable();
            
            // Duration limits
            $table->integer('max_duration_outside_range_minutes')->nullable()->comment('Maximum time allowed outside temperature range');
            $table->integer('warning_threshold_minutes')->nullable()->comment('Warning threshold for time outside range');
            
            // Compliance requirements
            $table->boolean('requires_continuous_monitoring')->default(false);
            $table->boolean('is_hazardous')->default(false)->comment('If true, temperature violations require immediate action');
            
            // Metadata
            $table->text('notes')->nullable();
            $table->string('regulation_reference')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['product_id', 'tenant_id']);
            $table->index('storage_type');
            $table->index('requires_continuous_monitoring');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_product_temperature_requirements');
    }
};
