<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция beauty_master_service — связь many-to-many мастеров и услуг.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('beauty_master_service')) {
            return;
        }

        Schema::create('beauty_master_service', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('master_id')->constrained('beauty_masters')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('beauty_services')->cascadeOnDelete();

            $table->unsignedBigInteger('custom_price_kopecks')->nullable();
            $table->unsignedSmallInteger('custom_duration_minutes')->nullable();

            $table->timestamps();

            $table->unique(['master_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_master_service');
    }
};
