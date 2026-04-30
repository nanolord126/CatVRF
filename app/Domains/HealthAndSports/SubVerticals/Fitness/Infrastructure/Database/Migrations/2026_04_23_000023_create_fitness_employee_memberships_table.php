<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_employee_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('corporate_enrollment_id')->constrained('fitness_corporate_enrollments')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('fitness_clients')->onDelete('cascade');
            
            $table->integer('remaining_visits')->default(0);
            $table->integer('total_visits')->nullable();
            $table->enum('status', ['active', 'inactive', 'expired'])->default('active');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['corporate_enrollment_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->unique(['corporate_enrollment_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_employee_memberships');
    }
};
