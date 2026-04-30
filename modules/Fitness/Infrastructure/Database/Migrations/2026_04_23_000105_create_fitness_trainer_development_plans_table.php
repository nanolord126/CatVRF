<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_trainer_development_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            $table->foreignId('trainer_id')->constrained('fitness_trainers')->onDelete('cascade');
            
            // Plan details
            $table->string('goal', 255)->comment('Development goal');
            $table->date('target_date')->nullable()->comment('Target completion date');
            $table->text('description')->nullable();
            
            // Required courses (JSON)
            $table->json('required_courses')->nullable()->comment('Array of required courses with due dates');
            
            // Progress
            $table->integer('progress')->default(0)->comment('Progress percentage (0-100)');
            $table->enum('status', ['active', 'completed', 'on_hold', 'cancelled'])->default('active');
            
            // Mentor
            $table->foreignId('mentor_id')->nullable()->constrained('fitness_trainers')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['trainer_id', 'status']);
            $table->index('target_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_trainer_development_plans');
    }
};
