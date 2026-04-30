<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_corporate_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('corporate_enrollment_id')->constrained('fitness_corporate_enrollments')->onDelete('cascade');
            
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('attendance_rate', 5, 2)->default(0);
            $table->integer('active_employees')->default(0);
            $table->integer('total_workouts')->default(0);
            $table->json('top_workouts')->nullable();
            $table->json('employee_stats')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'period_start', 'period_end']);
            $table->index(['corporate_enrollment_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_corporate_reports');
    }
};
