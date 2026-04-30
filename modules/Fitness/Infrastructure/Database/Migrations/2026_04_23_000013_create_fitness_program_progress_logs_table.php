<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_program_progress_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('enrollment_id')
                ->constrained('fitness_client_program_enrollments')
                ->onDelete('cascade');
            
            $table->timestamp('date');
            
            $table->json('metrics')->nullable()->comment('Custom metrics');
            $table->text('notes')->nullable();
            $table->json('photos')->nullable()->comment('Photo URLs');
            
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('body_fat_percentage', 5, 2)->nullable();
            $table->json('measurements')->nullable()->comment('Chest, waist, hips, arms, legs');
            
            $table->integer('wellbeing_score')->nullable()->comment('1-10 scale');
            $table->integer('energy_level')->nullable()->comment('1-10 scale');
            $table->integer('sleep_quality')->nullable()->comment('1-10 scale');
            
            $table->boolean('completed_workout')->default(false);
            $table->text('trainer_notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'enrollment_id']);
            $table->index(['enrollment_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_program_progress_logs');
    }
};
