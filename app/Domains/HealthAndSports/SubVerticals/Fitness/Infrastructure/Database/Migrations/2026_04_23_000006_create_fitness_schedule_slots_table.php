<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('venue_id')->constrained('fitness_venues')->onDelete('cascade');
            $table->foreignId('trainer_id')->constrained('fitness_trainers')->onDelete('cascade');
            $table->foreignId('workout_type_id')->constrained('fitness_workout_types')->onDelete('cascade');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->integer('capacity')->default(20);
            $table->integer('booked_count')->default(0);
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable();
            $table->timestamp('recurrence_end')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'venue_id', 'start_time']);
            $table->index(['tenant_id', 'trainer_id', 'start_time']);
            $table->index(['tenant_id', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_schedule_slots');
    }
};
