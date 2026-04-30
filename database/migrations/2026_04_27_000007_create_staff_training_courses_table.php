<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_training_courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('title');
            $table->text('description');
            $table->string('category');
            $table->integer('duration_hours');
            $table->integer('difficulty_level'); // 1-5
            $table->json('required_skills')->nullable();
            $table->json('acquired_skills')->nullable();
            $table->integer('points_reward')->default(0);
            $table->string('external_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('tenant_id');
            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_training_courses');
    }
};
