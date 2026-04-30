<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_client_program_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')
                ->constrained('fitness_clients')
                ->onDelete('cascade');
            $table->foreignId('seasonal_program_id')
                ->constrained('fitness_seasonal_programs')
                ->onDelete('cascade');
            
            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            
            $table->decimal('progress_percent', 5, 2)->default(0.00);
            
            $table->enum('status', [
                'active',
                'completed',
                'paused',
                'dropped',
                'medical_drop',
            ])->default('active');
            
            $table->json('initial_metrics')->nullable()->comment('Weight, body fat, measurements');
            $table->json('final_metrics')->nullable();
            
            $table->text('notes')->nullable();
            $table->text('drop_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['client_id', 'seasonal_program_id'], 'unique_client_program');
            $table->index(['tenant_id', 'status']);
            $table->index(['seasonal_program_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_client_program_enrollments');
    }
};
