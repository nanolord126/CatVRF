<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица chat_rooms — чат-комнаты для Communication домена.
 *
 * Поддерживает: P2P (клиент-мастер), групповые чаты (тендер),
 * техподдержку (support), B2B-переговоры.
 * Реал-тайм через Laravel Echo + Redis (канал: chat.{roomId}).
 * RealtimeChatService управляет статусами и участниками.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_rooms', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('business_group_id')->nullable()->index();

            // Тип комнаты
            $table->enum('type', [
                'p2p',          // клиент ↔ мастер/продавец
                'group',        // групповой чат (тендер, проект)
                'support',      // обращение в поддержку
                'b2b',          // B2B переговоры
                'broadcast',    // рассылка от платформы
            ])->default('p2p')->index();

            // Контекст (к какой сущности привязан чат)
            $table->string('context_type')->nullable()->index()->comment('Order, Appointment, Booking, Tender...');
            $table->unsignedBigInteger('context_id')->nullable()->index();

            // Вертикаль (для фильтрации и аналитики)
            $table->string('vertical')->nullable()->index()->comment('beauty, food, furniture...');

            // Метаданные
            $table->string('title')->nullable()->comment('Название чата (для группового)');
            $table->json('participants')->comment('Array of user_id');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->integer('messages_count')->default(0);
            $table->timestamp('last_message_at')->nullable()->index();

            // Статус
            $table->enum('status', ['active', 'closed', 'archived'])->default('active')->index();
            $table->timestamp('closed_at')->nullable();

            $table->json('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['context_type', 'context_id']);
            $table->index(['tenant_id', 'vertical', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_rooms');
    }
};
