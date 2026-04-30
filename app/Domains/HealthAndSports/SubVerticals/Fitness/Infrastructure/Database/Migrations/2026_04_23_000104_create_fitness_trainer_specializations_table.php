<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_trainer_specializations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            $table->foreignId('trainer_id')->constrained('fitness_trainers')->onDelete('cascade');
            
            // Specialization details
            $table->enum('specialization', ['Prenatal', 'Kids', 'Senior', 'Postpartum', 'Rehabilitation', 'Sports Performance'])->nullable();
            $table->enum('level', ['basic', 'advanced', 'master'])->default('basic');
            
            // Link to certification
            $table->foreignId('certification_id')->nullable()->constrained('fitness_trainer_certifications')->onDelete('set null');
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->date('expiry_date')->nullable()->comment('If tied to certification expiry');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['trainer_id', 'specialization', 'is_active']);
            $table->index(['specialization', 'is_active']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_trainer_specializations');
    }
};
