<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('activity_date')->unique();
            $table->integer('action_count')->unsigned()->default(0);
            $table->json('actions')->nullable(); // AR-try-on, views, reviews, cross-vertical, etc.
            $table->integer('streak_days')->unsigned()->default(0);
            $table->integer('acceleration_days')->unsigned()->default(0); // days reduced from hold
            $table->boolean('has_claimed_yield')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'activity_date']);
            $table->index(['tenant_id', 'user_id']);
            $table->index('activity_date');
            $table->index('streak_days');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_activity_logs');
    }
};
