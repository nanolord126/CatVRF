<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create user_quest_progress table
 *
 * Tracks individual user progress on daily quests.
 * Links users to quests and records completion status, progress, and rewards claimed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_quest_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index();
            
            // User and quest association
            $table->uuid('user_id')->index()->comment('User attempting the quest');
            $table->uuid('quest_id')->index()->comment('Quest being attempted');
            $table->date('quest_date')->index()->comment('Date of quest instance');
            
            // Progress tracking
            $table->integer('progress')->default(0)->comment('Progress toward completion');
            $table->integer('target')->default(1)->comment('Target for completion');
            $table->json('progress_data')->nullable()->comment('Detailed progress data');
            
            // Status
            $table->string('status', 20)->default('in_progress')->comment('in_progress, completed, claimed, expired');
            $table->timestamp('started_at')->nullable()->comment('When user started quest');
            $table->timestamp('completed_at')->nullable()->comment('When quest was completed');
            $table->timestamp('claimed_at')->nullable()->comment('When rewards were claimed');
            
            // Rewards
            $table->boolean('rewards_claimed')->default(false)->comment('Have rewards been claimed');
            $table->integer('bonus_reward_claimed')->default(0)->comment('Bonus reward claimed');
            $table->integer('hold_reduction_claimed')->default(0)->comment('Hold reduction claimed');
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 36)->nullable()->index();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->unique(['user_id', 'quest_id', 'quest_date']);
            $table->index(['user_id', 'quest_date', 'status']);
            $table->index(['quest_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_quest_progress');
    }
};
