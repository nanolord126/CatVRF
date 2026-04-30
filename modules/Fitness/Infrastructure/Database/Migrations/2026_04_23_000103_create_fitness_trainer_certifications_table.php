<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_trainer_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            $table->foreignId('trainer_id')->constrained('fitness_trainers')->onDelete('cascade');
            
            // Certification details
            $table->enum('certification_type', ['internal', 'external')->default('external');
            $table->string('name', 255)->comment('e.g., NASM CPT, Prenatal Fitness Specialist');
            $table->string('issuer', 255)->nullable()->comment('Issuing organization');
            $table->string('certificate_number')->nullable()->comment('Certificate ID or number');
            
            // Dates
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            
            // Document
            $table->string('document_file')->nullable()->comment('Path to uploaded certificate document');
            
            // Status
            $table->enum('status', ['active', 'expired', 'pending_verification', 'revoked'])->default('pending_verification');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Link to test result for internal certifications
            $table->foreignId('test_result_id')->nullable()->constrained('fitness_certification_test_results')->onDelete('set null');
            
            // Critical flag for safety certifications
            $table->boolean('is_critical')->default(false)->comment('Critical for safety (CPR, First Aid, specialized)');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['trainer_id', 'status']);
            $table->index('expiry_date');
            $table->index(['status', 'expiry_date']);
            $table->index('is_critical');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_trainer_certifications');
    }
};
