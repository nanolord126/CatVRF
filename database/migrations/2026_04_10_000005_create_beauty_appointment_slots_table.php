<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция beauty_appointment_slots — слоты доступности мастеров.
 *
 * Статусы: free, reserved, booked, blocked.
 * Резерв = 20 минут (reserved_until).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beauty_appointment_slots', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('master_id')->constrained('beauty_masters')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->index();
            $table->string('correlation_id')->nullable()->index();

            $table->date('date')->index();
            $table->time('start_time');
            $table->time('end_time');

            $table->string('status', 16)->default('free')->index();
            $table->timestamp('reserved_until')->nullable();

            $table->timestamps();

            $table->index(['master_id', 'date', 'status']);
            $table->unique(['master_id', 'date', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_appointment_slots');
    }
};
