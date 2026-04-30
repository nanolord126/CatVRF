<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_senior_program_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained('fitness_clients')->onDelete('cascade');
            $table->foreignId('senior_program_id')->constrained('fitness_senior_programs')->onDelete('cascade');
            
            $table->date('start_date');
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->enum('status', ['pending_clearance', 'active', 'completed', 'cancelled'])->default('pending_clearance');
            $table->enum('medical_clearance_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->date('medical_clearance_date')->nullable();
            $table->json('initial_assessment')->nullable();
            $table->json('final_assessment')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['senior_program_id', 'status']);
            $table->unique(['client_id', 'senior_program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_senior_program_enrollments');
    }
};
