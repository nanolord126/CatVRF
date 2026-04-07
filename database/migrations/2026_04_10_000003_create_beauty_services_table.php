<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция beauty_services — услуги салонов красоты.
 *
 * Поддерживает B2C (price_kopecks) и B2B (price_b2b_kopecks) цены.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beauty_services', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('salon_id')->constrained('beauty_salons')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->index();
            $table->string('correlation_id')->nullable()->index();

            $table->string('name');
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedBigInteger('price_kopecks');
            $table->unsignedBigInteger('price_b2b_kopecks')->nullable();
            $table->boolean('requires_consultation')->default(false);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['salon_id', 'is_active']);
            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_services');
    }
};
