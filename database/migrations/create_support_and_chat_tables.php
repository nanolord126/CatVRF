<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Таблица тикетов (Центральная БД для глобальной поддержки)
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index(); // От какого бизнеса (если от бизнеса)
            $table->unsignedBigInteger('user_id')->index(); // Кто создал (владелец бизнеса или юзер)
            $table->string('subject');
            $table->string('category'); // 'billing', 'technical', 'fraud_dispute', 'feature_request'
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['open', 'pending', 'resolved', 'closed'])->default('open');
            $table->string('correlation_id')->unique();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Таблица сообщений (Центральная БД для единого чата с поддержкой)
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('sender_id'); // User ID или Admin ID
            $table->boolean('is_admin_reply')->default(false);
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // Таблица B2B диалогов (Внутренняя связь между бизнесами/юзерами)
        // ВАЖНО: Может находиться в схеме тенанта, но для Cross-tenant чатов лучше в Central
        Schema::create('platform_chats', function (Blueprint $table) {
            $table->id();
            $table->string('from_tenant_id')->nullable();
            $table->string('to_tenant_id')->nullable();
            $table->unsignedBigInteger('from_user_id');
            $table->unsignedBigInteger('to_user_id');
            $table->string('context_type')->nullable(); // 'b2b_order', 'hr_task'
            $table->unsignedBigInteger('context_id')->nullable();
            $table->string('chat_hash')->unique(); // hash(min_id, max_id) для уникальности диалога
            $table->timestamps();
        });

        Schema::create('platform_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_chat_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('sender_id');
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_chat_messages');
        Schema::dropIfExists('platform_chats');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_tickets');
    }
};
