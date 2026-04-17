<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_slot_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64)->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('slot_id')->constrained('education_slots')->onDelete('cascade');
            $table->string('booking_reference', 64)->unique();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'])->default('pending');
            $table->timestamp('booked_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('attended_at')->nullable();
            $table->string('biometric_hash')->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'slot_id']);
            $table->index(['slot_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('booked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_slot_bookings');
    }
};
