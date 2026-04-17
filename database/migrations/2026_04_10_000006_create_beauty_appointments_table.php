<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция beauty_appointments — записи к мастерам.
 *
 * Статусы: pending, confirmed, in_progress, completed, cancelled, no_show.
 * Штрафы за отмену: cancellation_penalty_kopecks.
 * Финальная цена может отличаться от исходной (дополнительные услуги).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('beauty_appointments')) {
            return;
        }

        Schema::create('beauty_appointments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('salon_id')->constrained('beauty_salons')->index();
            $table->foreignId('master_id')->constrained('beauty_masters')->index();
            $table->foreignId('service_id')->constrained('beauty_services')->index();
            $table->foreignId('client_id')->constrained('users')->index();
            $table->foreignId('slot_id')->nullable()->constrained('beauty_appointment_slots')->nullOnDelete();
            $table->foreignId('tenant_id')->constrained()->index();
            $table->foreignId('business_group_id')->nullable()->constrained()->index();
            $table->string('correlation_id')->nullable()->index();
            $table->string('idempotency_key')->nullable()->unique();

            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status', 24)->default('pending')->index();

            $table->unsignedBigInteger('price_kopecks');
            $table->unsignedBigInteger('final_price_kopecks')->nullable();

            $table->text('client_comment')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->unsignedBigInteger('cancellation_penalty_kopecks')->default(0);
            $table->boolean('is_cancelled_by_master')->default(false);

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();

            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['master_id', 'starts_at']);
            $table->index(['salon_id', 'starts_at']);
            $table->index(['tenant_id', 'status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_appointments');
    }
};
