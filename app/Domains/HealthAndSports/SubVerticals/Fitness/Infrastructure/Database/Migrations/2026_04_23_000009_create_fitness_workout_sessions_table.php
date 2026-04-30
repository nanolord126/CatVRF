<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_workout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('schedule_slot_id')->constrained('fitness_schedule_slots')->onDelete('cascade');
            $table->foreignId('trainer_id')->constrained('fitness_trainers')->onDelete('cascade');
            $table->foreignId('venue_id')->constrained('fitness_venues')->onDelete('cascade');
            $table->foreignId('workout_type_id')->constrained('fitness_workout_types')->onDelete('cascade');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->integer('actual_participants')->default(0);
            $table->text('trainer_notes')->nullable();
            $table->json('exercises_performed')->nullable();
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->integer('total_ratings')->default(0);
            $table->json('equipment_used')->nullable();
            $table->string('music_playlist')->nullable();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('humidity', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'trainer_id', 'start_time']);
            $table->index(['tenant_id', 'venue_id', 'start_time']);
            $table->index(['tenant_id', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_workout_sessions');
    }
};
