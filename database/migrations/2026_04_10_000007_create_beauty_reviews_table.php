<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция beauty_reviews — отзывы клиентов.
 *
 * Рейтинг 1–5, фото, модерация (is_approved, is_reported).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('beauty_reviews')) {
            return;
        }

        Schema::create('beauty_reviews', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('appointment_id')->constrained('beauty_appointments')->cascadeOnDelete();
            $table->foreignId('salon_id')->constrained('beauty_salons')->index();
            $table->foreignId('master_id')->constrained('beauty_masters')->index();
            $table->foreignId('client_id')->constrained('users')->index();
            $table->foreignId('tenant_id')->constrained()->index();
            $table->string('correlation_id')->nullable()->index();

            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('text')->nullable();
            $table->json('photo_paths')->nullable();
            $table->json('tags')->nullable();

            $table->boolean('is_approved')->default(false);
            $table->boolean('is_reported')->default(false);

            $table->timestamps();

            $table->index(['salon_id', 'is_approved']);
            $table->index(['master_id', 'is_approved']);
            $table->index(['client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_reviews');
    }
};
