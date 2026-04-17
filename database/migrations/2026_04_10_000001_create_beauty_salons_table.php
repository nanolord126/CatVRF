<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция beauty_salons — салоны красоты.
 *
 * Обязательные поля по канону: uuid, correlation_id, tags (json), tenant_id, business_group_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('beauty_salons')) {
            return;
        }

        Schema::create('beauty_salons', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->index();
            $table->foreignId('business_group_id')->nullable()->constrained()->index();
            $table->string('correlation_id')->nullable()->index();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address');
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lon', 11, 8)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('working_hours')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();

            $table->decimal('rating', 3, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);

            $table->timestamps();

            $table->index(['is_active', 'is_verified']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_salons');
    }
};
