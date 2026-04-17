<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_driver_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('taxi_drivers')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('achievement_code', 100);
            $table->string('achievement_name', 255);
            $table->text('achievement_description')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 100)->nullable();
            $table->timestamps();

            $table->unique(['driver_id', 'achievement_code']);
            $table->index(['driver_id', 'tenant_id']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_driver_achievements');
    }
};
