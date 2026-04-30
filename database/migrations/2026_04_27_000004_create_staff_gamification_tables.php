<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Бейджи
        Schema::create('staff_badges', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->default('#3B82F6');
            $table->string('category')->default('achievement'); // achievement, milestone, special
            
            // Условия получения
            $table->json('requirements')->nullable();
            $table->integer('points_reward')->default(0);
            
            // Статистика
            $table->integer('total_earned')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'category']);
        });

        // Достижения сотрудников
        Schema::create('staff_achievements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('badge_id')->constrained('staff_badges')->onDelete('cascade');
            
            // Дата получения
            $table->timestamp('earned_at')->useCurrent();
            
            // Контекст
            $table->json('context')->nullable(); // Дополнительные данные о достижении
            
            $table->timestamps();
            
            $table->unique(['staff_id', 'badge_id']);
            $table->index(['tenant_id', 'staff_id']);
            $table->index('earned_at');
        });

        // Челленджи
        Schema::create('staff_challenges', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('weekly'); // daily, weekly, monthly, custom
            $table->string('category')->default('performance'); // performance, sales, teamwork, learning
            
            // Период
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('ends_at')->nullable();
            
            // Условия и награды
            $table->json('requirements')->nullable();
            $table->integer('points_reward')->default(0);
            $table->foreignId('badge_id')->nullable()->constrained('staff_badges')->onDelete('set null');
            
            // Статус
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->integer('participants_count')->default(0);
            $table->integer('completions_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'type']);
        });

        // Участие в челленджах
        Schema::create('staff_challenge_participants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('challenge_id')->constrained('staff_challenges')->onDelete('cascade');
            
            // Прогресс
            $table->decimal('progress', 5, 2)->default(0);
            $table->json('progress_data')->nullable();
            
            // Статус
            $table->string('status')->default('in_progress'); // in_progress, completed, abandoned
            $table->timestamp('completed_at')->nullable();
            
            // Рейтинг
            $table->integer('rank')->nullable();
            $table->integer('points_earned')->default(0);
            
            $table->timestamps();
            
            $table->unique(['staff_id', 'challenge_id']);
            $table->index(['tenant_id', 'challenge_id']);
        });

        // Очки и уровни
        Schema::create('staff_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            
            // Баланс очков
            $table->integer('total_points')->default(0);
            $table->integer('available_points')->default(0);
            $table->integer('spent_points')->default(0);
            
            // Уровень
            $table->integer('level')->default(1);
            $table->integer('xp')->default(0);
            $table->integer('xp_to_next_level')->default(100);
            
            // Статистика
            $table->integer('challenges_completed')->default(0);
            $table->integer('badges_earned')->default(0);
            
            $table->timestamps();
            
            $table->unique('staff_id');
            $table->index(['tenant_id', 'level']);
        });

        // История очков
        Schema::create('staff_point_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            
            // Транзакция
            $table->integer('points_change');
            $table->string('type'); // earned, spent, adjusted
            $table->string('source'); // challenge, badge, manual, etc.
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            
            // Баланс после транзакции
            $table->integer('balance_after');
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'staff_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_point_history');
        Schema::dropIfExists('staff_points');
        Schema::dropIfExists('staff_challenge_participants');
        Schema::dropIfExists('staff_challenges');
        Schema::dropIfExists('staff_achievements');
        Schema::dropIfExists('staff_badges');
    }
};
