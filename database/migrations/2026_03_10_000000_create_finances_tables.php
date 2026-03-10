<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Миграция для таблиц финансового модуля.
     *
     * Создаёт таблицы для:
     * - payment_transactions (платёжные транзакции)
     * - wallet_cards (сохранённые платёжные карты)
     * - subscriptions (повторяющиеся подписки)
     * - ml_model_versions (версии ML-моделей)
     * - ml_model_predictions (предсказания ML-моделей)
     */
    public function up(): void
    {
        // Таблица платёжных транзакций
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id')->nullable()->unique(); // ID платежа в системе провайдера
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->decimal('amount', 12, 2); // Сумма платежа
            $table->enum('status', [
                'pending',      // Ожидание подтверждения
                'authorized',   // Авторизован (удержание)
                'settled',      // Завершён (списан)
                'failed',       // Ошибка
                'refunded',     // Возвращён
            ])->default('pending');
            $table->json('splits')->nullable(); // Распределение средств между участниками
            $table->json('metadata')->nullable(); // Дополнительные данные (order_id, order_type и т.д.)
            $table->uuid('correlation_id'); // ID цепочки транзакций для отслеживания
            $table->timestamp('captured_at')->nullable(); // Время финализации платежа
            $table->timestamps();

            // Индексы для быстрого поиска
            $table->index('payment_id');
            $table->index('user_id');
            $table->index('tenant_id');
            $table->index('status');
            $table->index('correlation_id');
            $table->index('created_at');
        });

        // Таблица сохранённых платёжных карт
        Schema::create('wallet_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('token'); // Токен карты от платёжного провайдера
            $table->string('card_last_four'); // Последние 4 цифры карты (****1234)
            $table->string('card_brand'); // Бренд карты (VISA, MASTERCARD и т.д.)
            $table->unsignedTinyInteger('exp_month'); // Месяц истечения (01-12)
            $table->unsignedSmallInteger('exp_year'); // Год истечения (2025, 2026...)
            $table->boolean('is_active')->default(true); // Активна ли карта
            $table->boolean('is_default')->default(false); // Карта по умолчанию
            $table->uuid('correlation_id'); // ID для отслеживания
            $table->timestamps();

            // Индексы
            $table->index('user_id');
            $table->index('tenant_id');
            $table->index('is_active');
            $table->index('is_default');
        });

        // Таблица повторяющихся подписок (автоплатежи)
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('wallet_card_id')->constrained('wallet_cards')->cascadeOnDelete();
            $table->decimal('amount', 12, 2); // Сумма платежа
            $table->enum('frequency', [
                'daily',
                'weekly',
                'monthly',
                'yearly',
            ])->default('monthly'); // Периодичность платежа
            $table->enum('status', [
                'active',       // Активна
                'paused',       // Приостановлена
                'cancelled',    // Отменена
                'failed',       // Ошибка
            ])->default('active');
            $table->timestamp('starts_at'); // Начало подписки
            $table->timestamp('ends_at')->nullable(); // Конец подписки
            $table->timestamp('last_payment_at')->nullable(); // Последний платёж
            $table->timestamp('next_payment_at')->nullable(); // Следующий платёж
            $table->uuid('correlation_id'); // ID для отслеживания
            $table->json('metadata')->nullable(); // Дополнительные данные
            $table->timestamps();

            // Индексы
            $table->index('user_id');
            $table->index('tenant_id');
            $table->index('status');
            $table->index('next_payment_at');
        });

        // Таблица версий ML-моделей
        Schema::create('ml_model_versions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название модели
            $table->string('version'); // Версия (v1.0, v1.1...)
            $table->enum('model_type', [
                'fraud_detection',
                'risk_scoring',
                'conversion_prediction',
            ]); // Тип модели
            $table->decimal('accuracy', 5, 4)->nullable(); // Точность (0.0000-1.0000)
            $table->decimal('precision', 5, 4)->nullable(); // Precision
            $table->decimal('recall', 5, 4)->nullable(); // Recall
            $table->decimal('f1_score', 5, 4)->nullable(); // F1 Score
            $table->json('metrics')->nullable(); // Дополнительные метрики
            $table->json('config')->nullable(); // Конфигурация модели
            $table->boolean('is_active')->default(false); // Активна ли модель в production
            $table->timestamp('deployed_at')->nullable(); // Время развёртывания
            $table->uuid('correlation_id'); // ID для отслеживания
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->timestamps();

            // Индексы
            $table->index('model_type');
            $table->index('is_active');
            $table->index('deployed_at');
        });

        // Таблица предсказаний ML-моделей
        Schema::create('ml_model_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ml_model_version_id')->constrained('ml_model_versions')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->boolean('is_fraud')->default(false); // Предсказание: мошенничество?
            $table->decimal('confidence', 5, 4); // Уверенность (0.0000-1.0000)
            $table->json('features')->nullable(); // Признаки, использованные для предсказания
            $table->uuid('correlation_id'); // ID для отслеживания
            $table->timestamps();

            // Индексы
            $table->index('ml_model_version_id');
            $table->index('transaction_id');
            $table->index('user_id');
            $table->index('tenant_id');
            $table->index('is_fraud');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_model_predictions');
        Schema::dropIfExists('ml_model_versions');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('wallet_cards');
        Schema::dropIfExists('payment_transactions');
    }
};
