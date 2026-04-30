<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_kids_health_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained('fitness_clients')->onDelete('cascade');
            
            $table->enum('age_group', ['preschool', 'school_6_8', 'school_9_12', 'teens'])->default('preschool');
            $table->date('birth_date')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone')->nullable();
            $table->string('parent_email')->nullable();
            $table->boolean('has_allergies')->default(false);
            $table->text('allergies')->nullable();
            $table->boolean('has_asthma')->default(false);
            $table->boolean('has_heart_condition')->default(false);
            $table->text('medications')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->json('physical_limitations')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['client_id']);
            $table->index(['tenant_id', 'age_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_kids_health_profiles');
    }
};
