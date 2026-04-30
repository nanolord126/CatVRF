<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_lab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('lab_test_type_id')->constrained('dental_lab_test_types');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('barcode')->unique();
            $table->enum('status', ['ordered', 'sample_collected', 'in_progress', 'completed', 'cancelled'])->default('ordered');
            $table->timestamp('sample_collected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
            $table->index('barcode');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_lab_tests');
    }
};
