<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Сообщения (внутренний чат)
        Schema::create('staff_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('staff')->onDelete('cascade');
            
            // Получатели
            $table->foreignId('recipient_id')->nullable()->constrained('staff')->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained('staff_message_groups')->onDelete('cascade');
            
            // Контент
            $table->text('content');
            $table->string('message_type')->default('text'); // text, image, file, voice, system
            
            // Вложения
            $table->json('attachments')->nullable();
            
            // Статус
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            // Ответ на сообщение
            $table->foreignId('reply_to_id')->nullable()->constrained('staff_messages')->onDelete('set null');
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'sender_id']);
            $table->index(['tenant_id', 'recipient_id']);
            $table->index(['tenant_id', 'group_id']);
            $table->index('created_at');
        });

        // Группы сообщений
        Schema::create('staff_message_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('group'); // group, channel, direct, project
            
            // Настройки
            $table->boolean('is_private')->default(false);
            $table->boolean('is_muted')->default(false);
            
            // Владелец
            $table->foreignId('owner_id')->constrained('staff')->onDelete('cascade');
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'type']);
        });

        // Участники групп
        Schema::create('staff_message_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('group_id')->constrained('staff_message_groups')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            
            // Роль в группе
            $table->string('role')->default('member'); // owner, admin, member
            
            // Настройки
            $table->boolean('is_muted')->default(false);
            $table->timestamp('last_read_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['group_id', 'staff_id']);
            $table->index(['tenant_id', 'group_id']);
        });

        // Объявления
        Schema::create('staff_announcements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('author_id')->constrained('staff')->onDelete('cascade');
            
            $table->string('title');
            $table->text('content');
            $table->string('type')->default('general'); // general, urgent, hr, it, sales
            
            // Приоритет
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            
            // Аудитория
            $table->json('target_audience')->nullable(); // everyone, department, specific_roles
            $table->json('target_departments')->nullable();
            $table->json('target_roles')->nullable();
            
            // Вложения
            $table->json('attachments')->nullable();
            
            // Публикация
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Статистика
            $table->integer('views_count')->default(0);
            $table->integer('reads_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_published']);
            $table->index('published_at');
        });

        // Чтение объявлений
        Schema::create('staff_announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('announcement_id')->constrained('staff_announcements')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            
            $table->timestamp('read_at')->useCurrent();
            
            $table->unique(['announcement_id', 'staff_id']);
            $table->index(['tenant_id', 'announcement_id']);
        });

        // Реакции на сообщения
        Schema::create('staff_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('message_id')->constrained('staff_messages')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            
            $table->string('emoji');
            
            $table->timestamps();
            
            $table->unique(['message_id', 'staff_id', 'emoji']);
            $table->index(['tenant_id', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_message_reactions');
        Schema::dropIfExists('staff_announcement_reads');
        Schema::dropIfExists('staff_announcements');
        Schema::dropIfExists('staff_message_group_members');
        Schema::dropIfExists('staff_message_groups');
        Schema::dropIfExists('staff_messages');
    }
};
