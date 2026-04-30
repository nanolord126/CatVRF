<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_peer_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('reviewer_id');
            $table->unsignedBigInteger('reviewee_id');
            $table->integer('rating'); // 1-5
            $table->text('feedback');
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->enum('status', ['pending', 'completed', 'archived'])->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('tenant_id');
            $table->index('reviewer_id');
            $table->index('reviewee_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_peer_reviews');
    }
};
