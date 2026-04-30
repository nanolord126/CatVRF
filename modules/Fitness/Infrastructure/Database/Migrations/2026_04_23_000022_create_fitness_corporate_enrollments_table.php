<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_corporate_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('corporate_client_id')->constrained('fitness_corporate_clients')->onDelete('cascade');
            $table->foreignId('corporate_package_id')->constrained('fitness_corporate_packages')->onDelete('restrict');
            
            $table->integer('number_of_employees');
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['draft', 'active', 'suspended', 'completed', 'cancelled'])->default('draft');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'end_date']);
            $table->index(['corporate_client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_corporate_enrollments');
    }
};
