<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_test_id')->constrained('dental_lab_tests')->onDelete('cascade');
            $table->string('parameter_name');
            $table->string('parameter_value');
            $table->string('unit')->nullable();
            $table->string('reference_range')->nullable();
            $table->boolean('is_abnormal')->default(false);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('lab_test_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_lab_results');
    }
};
