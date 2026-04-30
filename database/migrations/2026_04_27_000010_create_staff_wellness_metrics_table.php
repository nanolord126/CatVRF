<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_wellness_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('employee_id');
            $table->integer('stress_level')->nullable(); // 0-100
            $table->integer('sleep_hours')->nullable();
            $table->integer('work_hours')->nullable();
            $table->integer('breaks_taken')->nullable();
            $table->integer('mood_score')->nullable(); // 0-10
            $table->integer('energy_level')->nullable(); // 0-10
            $table->integer('work_life_balance')->nullable(); // 0-10
            $table->decimal('steps_count', 10, 2)->nullable();
            $table->decimal('active_minutes', 10, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('tenant_id');
            $table->index('employee_id');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_wellness_metrics');
    }
};
