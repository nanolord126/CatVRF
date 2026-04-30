<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_certification_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            $table->foreignId('trainer_id')->constrained('fitness_trainers')->onDelete('cascade');
            
            // Test details
            $table->string('test_name', 255)->comment('Name of the certification test');
            $table->string('test_type', 50)->default('internal')->comment('internal or external');
            
            // Scores
            $table->decimal('theory_score', 5, 2)->nullable()->comment('Theory test score (0-100)');
            $table->decimal('practice_score', 5, 2)->nullable()->comment('Practical test score (0-100)');
            $table->decimal('total_score', 5, 2)->nullable()->comment('Total score (0-100)');
            
            // Pass/Fail
            $table->boolean('passed')->default(false);
            $table->timestamp('evaluated_at')->nullable();
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Feedback
            $table->text('feedback')->nullable()->comment('Evaluator feedback');
            $table->text('notes')->nullable();
            
            // Link to certification (if test leads to certification)
            $table->foreignId('certification_id')->nullable()->constrained('fitness_trainer_certifications')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['trainer_id', 'passed']);
            $table->index('evaluated_at');
            $table->index('total_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_certification_test_results');
    }
};
