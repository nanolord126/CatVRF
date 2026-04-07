<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция beauty_masters — мастера салонов красоты.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beauty_masters', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('salon_id')->constrained('beauty_salons')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tenant_id')->constrained()->index();
            $table->string('correlation_id')->nullable()->index();

            $table->string('full_name');
            $table->string('specialization');
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->text('bio')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('avatar_path')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();

            $table->decimal('rating', 3, 2)->default(5.00);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['salon_id', 'is_active']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_masters');
    }
};
