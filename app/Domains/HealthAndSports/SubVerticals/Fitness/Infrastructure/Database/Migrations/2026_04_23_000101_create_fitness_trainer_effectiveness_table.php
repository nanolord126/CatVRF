<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_trainer_effectiveness', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            $table->foreignId('trainer_id')->constrained('fitness_trainers')->onDelete('cascade');
            
            // Period
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            
            // Client Metrics (60% weight)
            $table->decimal('retention_rate', 5, 2)->nullable()->comment('Percentage of clients continuing after first month');
            $table->decimal('nps_score', 5, 2)->nullable()->comment('Net Promoter Score from client reviews (-100 to 100)');
            $table->decimal('avg_check_per_client', 10, 2)->nullable()->comment('Average revenue per client');
            $table->integer('repeat_bookings_count')->default(0)->comment('Count of returning clients');
            $table->decimal('churn_rate', 5, 2)->nullable()->comment('Percentage of clients lost');
            
            // Operational Metrics (25% weight)
            $table->decimal('occupancy_rate', 5, 2)->nullable()->comment('Schedule fill percentage');
            $table->decimal('avg_group_attendance', 5, 2)->nullable()->comment('Average attendance in group sessions');
            $table->integer('individual_sessions_count')->default(0)->comment('Number of 1-on-1 sessions conducted');
            $table->decimal('schedule_compliance', 5, 2)->nullable()->comment('On-time performance percentage');
            
            // Qualitative Metrics (15% weight)
            $table->decimal('manager_score', 3, 1)->nullable()->comment('Supervisor evaluation (0-10 scale)');
            $table->boolean('methodology_compliance')->default(true)->comment('Adherence to training standards');
            $table->integer('progress_photos_count')->default(0)->comment('Client progress documentation count');
            
            // Total Score
            $table->decimal('total_score', 5, 2)->default(0)->comment('Overall effectiveness score (0-100)');
            $table->enum('effectiveness_level', ['A+', 'A', 'B', 'C', 'D'])->default('B')->comment('Performance level');
            $table->text('recommendations')->nullable()->comment('AI-generated recommendations');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['trainer_id', 'period_end']);
            $table->index('total_score');
            $table->index('effectiveness_level');
            $table->index(['tenant_id', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_trainer_effectiveness');
    }
};
