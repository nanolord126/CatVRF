<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_workout_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('intensity', ['very_low', 'low', 'moderate', 'high', 'very_high']);
            $table->integer('duration_minutes')->default(60);
            $table->integer('calories_burn_estimate')->default(400);
            $table->json('equipment_needed')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_group')->default(false);
            $table->integer('max_participants')->default(1);
            $table->decimal('price', 10, 2)->default(0);
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'category']);
            $table->index(['tenant_id', 'intensity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_workout_types');
    }
};
