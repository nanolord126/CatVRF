<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->constrained('fitness_bookings')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('fitness_clients')->onDelete('cascade');
            $table->foreignId('schedule_slot_id')->constrained('fitness_schedule_slots')->onDelete('cascade');
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('present');
            $table->timestamp('check_in_time');
            $table->timestamp('check_out_time')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('trainer_id')->nullable()->constrained('fitness_trainers')->onDelete('set null');
            $table->json('performance_metrics')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'client_id']);
            $table->index(['tenant_id', 'schedule_slot_id']);
            $table->index(['tenant_id', 'check_in_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_attendances');
    }
};
