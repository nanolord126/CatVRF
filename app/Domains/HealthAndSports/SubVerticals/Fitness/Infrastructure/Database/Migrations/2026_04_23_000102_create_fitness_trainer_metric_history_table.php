<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_trainer_metric_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            $table->foreignId('trainer_id')->constrained('fitness_trainers')->onDelete('cascade');
            $table->foreignId('effectiveness_id')->constrained('fitness_trainer_effectiveness')->onDelete('cascade');
            
            // Metric change tracking
            $table->string('metric_name', 50)->comment('Name of the metric (e.g., retention_rate, nps_score)');
            $table->decimal('old_value', 10, 2)->nullable()->comment('Previous value');
            $table->decimal('new_value', 10, 2)->nullable()->comment('New value');
            $table->decimal('delta', 10, 2)->nullable()->comment('Change amount');
            
            // Change context
            $table->string('change_reason')->nullable()->comment('Reason for the change');
            $table->text('notes')->nullable()->comment('Additional notes');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['trainer_id', 'metric_name']);
            $table->index(['effectiveness_id', 'metric_name']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_trainer_metric_history');
    }
};
