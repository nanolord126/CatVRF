<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Peer reviews (отзывы между сотрудниками)
        Schema::create('staff_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('reviewee_id')->constrained('staff')->onDelete('cascade');
            
            // Оценка
            $table->decimal('overall_rating', 3, 2)->default(0); // 1-5
            $table->decimal('communication_rating', 3, 2)->nullable();
            $table->decimal('teamwork_rating', 3, 2)->nullable();
            $table->decimal('leadership_rating', 3, 2)->nullable();
            $table->decimal('technical_rating', 3, 2)->nullable();
            
            // Текст отзыва
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('comments')->nullable();
            
            // Статус
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->boolean('is_anonymous')->default(false);
            
            // Период
            $table->string('period')->default('monthly'); // weekly, monthly, quarterly
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['reviewer_id', 'reviewee_id', 'period'], 'unique_review_period');
            $table->index(['tenant_id', 'reviewee_id']);
            $table->index(['tenant_id', 'status']);
        });

        // Наставничество (mentoring)
        Schema::create('staff_mentoring', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('mentor_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('mentee_id')->constrained('staff')->onDelete('cascade');
            
            // Детали менторства
            $table->text('goals')->nullable();
            $table->string('focus_area')->nullable();
            $table->date('start_date')->useCurrent();
            $table->date('end_date')->nullable();
            
            // Статус
            $table->string('status')->default('active'); // active, paused, completed
            $table->text('status_notes')->nullable();
            
            // Сессии
            $table->integer('sessions_count')->default(0);
            $table->integer('sessions_completed')->default(0);
            
            // Оценка
            $table->decimal('effectiveness_rating', 3, 2)->nullable(); // Оценка ментором
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['mentor_id', 'mentee_id', 'status'], 'unique_mentoring');
            $table->index(['tenant_id', 'status']);
        });

        // Сессии менторства
        Schema::create('staff_mentoring_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('mentoring_id')->constrained('staff_mentoring')->onDelete('cascade');
            
            // Детали сессии
            $table->timestamp('scheduled_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_minutes')->default(60);
            
            // Темы и заметки
            $table->text('topics')->nullable();
            $table->text('notes')->nullable();
            $table->text('action_items')->nullable();
            
            // Статус
            $table->string('status')->default('scheduled'); // scheduled, completed, cancelled, no_show
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'mentoring_id']);
            $table->index('scheduled_at');
        });

        // Благодарности и комплименты
        Schema::create('staff_thanks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('staff')->onDelete('cascade');
            
            // Тип благодарности
            $table->string('type')->default('thank_you'); // thank_you, praise, recognition, help
            
            // Сообщение
            $table->text('message')->nullable();
            $table->string('badge_type')->nullable(); // quick_helper, team_player, etc.
            
            // Видимость
            $table->boolean('is_public')->default(true);
            
            // Связь с проектом/задачей
            $table->string('related_entity_type')->nullable();
            $table->unsignedBigInteger('related_entity_id')->nullable();
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'receiver_id']);
            $table->index(['tenant_id', 'created_at']);
        });

        // Внутренняя социальная сеть (посты)
        Schema::create('staff_posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('author_id')->constrained('staff')->onDelete('cascade');
            
            // Контент
            $table->text('content');
            $table->string('type')->default('post'); // post, announcement, achievement, question
            
            // Медиа
            $table->json('attachments')->nullable();
            
            // Взаимодействия
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            
            // Настройки
            $table->boolean('is_pinned')->default(false);
            $table->boolean('allow_comments')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'author_id']);
            $table->index(['tenant_id', 'created_at']);
        });

        // Комментарии к постам
        Schema::create('staff_post_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('post_id')->constrained('staff_posts')->onDelete('cascade');
            $table->foreignId('author_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('staff_post_comments')->onDelete('cascade');
            
            $table->text('content');
            $table->integer('likes_count')->default(0);
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'post_id']);
        });

        // Лайки
        Schema::create('staff_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('likeable_id');
            $table->string('likeable_type'); // staff_post, staff_post_comment
            
            $table->timestamps();
            
            $table->unique(['staff_id', 'likeable_id', 'likeable_type'], 'unique_like');
            $table->index(['tenant_id', 'likeable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_likes');
        Schema::dropIfExists('staff_post_comments');
        Schema::dropIfExists('staff_posts');
        Schema::dropIfExists('staff_thanks');
        Schema::dropIfExists('staff_mentoring_sessions');
        Schema::dropIfExists('staff_mentoring');
        Schema::dropIfExists('staff_reviews');
    }
};
