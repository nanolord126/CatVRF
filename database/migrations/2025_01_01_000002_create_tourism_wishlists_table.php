<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Tourism Wishlists Table
 * 
 * Migration for tourism wishlist with AI-powered recommendations.
 * Stores user wishlist items with preferences for personalized tour suggestions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tourism_wishlists', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tour_id')->constrained('tours')->onDelete('cascade');
            $table->integer('priority')->default(5)->comment('1-10, higher is more important');
            $table->text('notes')->nullable();
            $table->json('budget_range')->nullable()->comment('[min, max] budget in kopecks');
            $table->json('preferred_dates')->nullable()->comment('array of date ranges');
            $table->integer('group_size')->nullable()->comment('expected group size');
            $table->text('special_requests')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'tour_id']);
            $table->index(['tenant_id', 'priority']);
            $table->index(['user_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tourism_wishlists');
    }
};
