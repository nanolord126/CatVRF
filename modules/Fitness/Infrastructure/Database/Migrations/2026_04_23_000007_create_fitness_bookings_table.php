<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained('fitness_clients')->onDelete('cascade');
            $table->foreignId('schedule_slot_id')->constrained('fitness_schedule_slots')->onDelete('cascade');
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show', 'waitlist'])->default('pending');
            $table->foreignId('membership_id')->nullable()->constrained('fitness_memberships')->onDelete('set null');
            $table->timestamp('booked_at');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'client_id', 'status']);
            $table->index(['tenant_id', 'schedule_slot_id']);
            $table->index(['tenant_id', 'booked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_bookings');
    }
};
