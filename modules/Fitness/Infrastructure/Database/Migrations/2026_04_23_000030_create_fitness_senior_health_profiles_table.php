<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_senior_health_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained('fitness_clients')->onDelete('cascade');
            
            $table->boolean('has_heart_condition')->default(false);
            $table->boolean('has_diabetes')->default(false);
            $table->boolean('has_joint_problems')->default(false);
            $table->boolean('has_mobility_limitations')->default(false);
            $table->boolean('has_balance_issues')->default(false);
            $table->text('medications')->nullable();
            $table->text('allergies')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->enum('fitness_level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->json('physical_limitations')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['client_id']);
            $table->index(['tenant_id', 'fitness_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_senior_health_profiles');
    }
};
