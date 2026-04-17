<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица notification_preferences — настройки каналов уведомлений пользователя.
 *
 * Каждый пользователь может управлять предпочтениями по каждому типу уведомления
 * и каналу (email, push, telegram, sms, in_app, slack).
 * Quiet hours — часы когда уведомления откладываются (кроме critical/fraud).
 * NotificationPreferencesService читает эту таблицу перед отправкой.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('notification_preferences')) {
            return;
        }

        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('business_group_id')->nullable()->index();

            // Канал и тип уведомления
            $table->string('notification_type')->index()->comment('order_status, payment, fraud_alert, promo, ai_result, chat...');
            $table->enum('channel', ['email', 'push', 'telegram', 'sms', 'in_app', 'slack'])->index();

            // Включён ли канал для данного типа
            $table->boolean('is_enabled')->default(true)->index();

            // Quiet hours (не беспокоить)
            $table->time('quiet_from')->nullable()->comment('Начало тихого часа (UTC), напр. 23:00');
            $table->time('quiet_to')->nullable()->comment('Конец тихого часа (UTC), напр. 07:00');
            $table->boolean('override_quiet_for_critical')->default(true)
                ->comment('Fraud/security уведомления всегда проходят несмотря на quiet hours');

            // Частота (чтобы не заспамить)
            $table->enum('frequency', ['instant', 'digest_daily', 'digest_weekly', 'never'])->default('instant');
            $table->time('digest_send_at')->nullable()->comment('Время дайджеста (UTC)');

            $table->json('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            // Один пользователь — одна запись на тип+канал
            $table->unique(['user_id', 'notification_type', 'channel'], 'uq_notif_pref_user_type_channel');
            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
