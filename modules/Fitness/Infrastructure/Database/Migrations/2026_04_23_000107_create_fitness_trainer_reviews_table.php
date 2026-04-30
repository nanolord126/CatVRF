<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_trainer_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            $table->foreignId('trainer_id')->constrained('fitness_trainers')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('fitness_clients')->onDelete('cascade');
            
            // Rating (NPS scale 0-10)
            $table->integer('rating')->comment('Rating 0-10 for NPS calculation');
            
            // Review content
            $table->text('review_text')->nullable();
            
            // Verification
            $table->boolean('is_verified')->default(false)->comment('Verified review from confirmed attendance');
            $table->timestamp('verified_at')->nullable();
            
            // Sentiment analysis (AI)
            $table->string('sentiment')->nullable()->comment('positive, negative, neutral');
            $table->decimal('sentiment_score', 5, 2)->nullable()->comment('Sentiment confidence score');
            
            // For NPS classification
            $table->enum('nps_category', ['promoter', 'passive', 'detractor'])->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['trainer_id', 'is_verified']);
            $table->index(['trainer_id', 'rating']);
            $table->index('created_at');
            $table->index('nps_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_trainer_reviews');
    }
};
