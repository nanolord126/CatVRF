<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_treatment_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained('dental_treatment_plans')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('tooth_number')->nullable(); // Specific tooth if applicable
            $table->enum('status', ['pending', 'scheduled', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->decimal('cost', 10, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->date('scheduled_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['treatment_plan_id', 'sort_order']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_treatment_steps');
    }
};
