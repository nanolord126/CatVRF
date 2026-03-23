<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chat_conversations')) {
            return;
        }

        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            
            // Тип чата: private, group, support
            $table->string('type')->default('private')->index();
            $table->jsonb('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->comment('Таблица диалогов (комнат) чата');
        });

        Schema::create('chat_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('last_read_at')->nullable();
            $table->timestamps();
            
            $table->unique(['conversation_id', 'user_id']);
            $table->comment('Участники диалогов');
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            
            $table->text('content');
            $table->string('type')->default('text')->comment('text, image, file, system');
            $table->jsonb('payload')->nullable();
            
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            
            $table->comment('Сообщения чата');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_participants');
        Schema::dropIfExists('chat_conversations');
    }
};
