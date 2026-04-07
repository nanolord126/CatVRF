<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица payment_transactions_v2 — Clean Architecture слой Payments.
 * Используется Infrastructure/Models/PaymentModel.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_transactions_v2')) {
            return;
        }

        Schema::create('payment_transactions_v2', static function (Blueprint $table): void {
            $table->comment('Платёжные транзакции (Clean Architecture v2)');

            $table->id();
            $table->uuid('uuid')->unique()->index()->comment('UUID транзакции');
            $table->unsignedBigInteger('tenant_id')->index()->comment('ID тенанта');
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('ID пользователя');
            $table->string('idempotency_key', 255)->index()->comment('Ключ идемпотентности');
            $table->string('provider_code', 64)->index()->comment('Код шлюза: tinkoff, sber, tochka');
            $table->string('provider_payment_id', 255)->nullable()->index()->comment('ID платежа на стороне шлюза');
            $table->string('payment_url', 2048)->nullable()->comment('URL платёжной страницы');
            $table->unsignedBigInteger('amount')->comment('Сумма в копейках');
            $table->char('currency', 3)->default('RUB')->comment('Валюта ISO 4217');
            $table->string('status', 32)->default('pending')->index()->comment('Статус платежа');
            $table->boolean('is_recurring')->default(false)->comment('Рекуррентный платёж');
            $table->string('rebill_id', 255)->nullable()->comment('RebillId для рекуррентов (Tinkoff)');
            $table->string('correlation_id', 36)->nullable()->index()->comment('Correlation ID для трейсинга');
            $table->json('metadata')->nullable()->comment('Произвольные мета-данные');
            $table->json('tags')->nullable()->comment('Теги для аналитики');
            $table->float('ml_fraud_score')->nullable()->comment('ML fraud score 0–1');
            $table->timestamp('captured_at')->nullable()->comment('Время capture');
            $table->timestamp('refunded_at')->nullable()->comment('Время refund');
            $table->timestamps();
            $table->softDeletes();

            // Составные индексы
            $table->index(['tenant_id', 'status'], 'pt2_tenant_status');
            $table->index(['tenant_id', 'user_id'], 'pt2_tenant_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions_v2');
    }
};


