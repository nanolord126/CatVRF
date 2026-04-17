<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица chat_messages — сообщения в чат-комнатах.
 *
 * Поддерживает: текст, изображения, файлы, аудио, видео,
 * системные сообщения (статус заказа, уведомление).
 * Реал-тайм broadcast: NewChatMessageEvent → Echo → Livewire.
 * Удаление — soft (is_deleted, deleted_at) для аудита.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('chat_room_id')->constrained('chat_rooms')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('business_group_id')->nullable()->index();

            // Отправитель (nullable для system-сообщений)
            $table->unsignedBigInteger('sender_user_id')->nullable()->index();
            $table->boolean('is_system_message')->default(false)->index();

            // Контент
            $table->enum('type', [
                'text',
                'image',
                'file',
                'audio',
                'video',
                'order_update',     // изменение статуса заказа
                'payment_request',  // запрос оплаты (inline)
                'location',         // геопозиция
                'system',           // служебные (welcome, member joined и т.д.)
            ])->default('text')->index();

            $table->text('body')->nullable();

            // Вложения
            $table->string('attachment_path')->nullable();
            $table->json('attachment_meta')->nullable()
                ->comment('{"filename":"photo.jpg","size":204800,"mime":"image/jpeg","width":1080,"height":1080}');

            // Inline-данные (для order_update / payment_request)
            $table->json('inline_data')->nullable()
                ->comment('{"order_id":1,"status":"delivered"} или {"amount":"1500.00","currency":"RUB"}');

            // Статус прочтения (для P2P — один получатель)
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();

            // Статус доставки
            $table->enum('delivery_status', ['sent', 'delivered', 'failed'])->default('sent');

            // Ответ на сообщение (thread)
            $table->unsignedBigInteger('reply_to_message_id')->nullable()->index();

            // Мягкое удаление
            $table->boolean('is_deleted')->default(false)->index();
            $table->timestamp('deleted_at')->nullable();

            // Реакции (like, heart, ...)
            $table->json('reactions')->nullable()
                ->comment('{"👍":[1,2,3],"❤️":[5]}');

            $table->json('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['chat_room_id', 'created_at']);
            $table->index(['tenant_id', 'sender_user_id']);
            $table->index(['chat_room_id', 'is_read', 'is_deleted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
