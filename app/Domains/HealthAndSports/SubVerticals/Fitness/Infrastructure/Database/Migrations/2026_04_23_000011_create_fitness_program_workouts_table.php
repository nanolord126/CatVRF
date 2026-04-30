<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_program_workouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('seasonal_program_id')
                ->constrained('fitness_seasonal_programs')
                ->onDelete('cascade');
            
            $table->integer('day_number')->comment('Day number in program (1-56 for 8 weeks)');
            $table->foreignId('workout_type_id')
                ->nullable()
                ->constrained('fitness_workout_types')
                ->onDelete('set null');
            $table->foreignId('trainer_id')
                ->nullable()
                ->constrained('fitness_trainers')
                ->onDelete('set null');
            $table->foreignId('schedule_slot_id')
                ->nullable()
                ->constrained('fitness_schedule_slots')
                ->onDelete('set null');
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->integer('rest_seconds')->default(60);
            
            $table->json('exercises')->nullable()->comment('Exercise configuration');
            $table->json('equipment_needed')->nullable();
            
            $table->boolean('is_rest_day')->default(false);
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'seasonal_program_id']);
            $table->index(['seasonal_program_id', 'day_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_program_workouts');
    }
};
