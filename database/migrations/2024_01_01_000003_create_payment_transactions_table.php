<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_transactions')) {
            return;
        }

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index()->comment('Уникальный идентификатор');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->comment('ID тенанта');
            $table->string('idempotency_key')->unique()->comment('Ключ идемпотентности');
            $table->string('provider_code')->comment('Код платежного шлюза (Tinkoff, Sber, etc.)');
            $table->string('provider_payment_id')->nullable()->index()->comment('ID платежа в системе провайдера');
            $table->string('status')->comment('Статус платежа (pending, captured, etc.)');
            $table->bigInteger('amount')->comment('Сумма в копейках');
            $table->boolean('hold')->default(false)->comment('Платеж с холдом');
            $table->timestamp('captured_at')->nullable()->comment('Время подтверждения списания');
            $table->timestamp('refunded_at')->nullable()->comment('Время возврата');
            $table->string('correlation_id')->nullable()->index()->comment('ID для сквозной трассировки');
            $table->jsonb('meta')->nullable()->comment('Дополнительные данные от провайдера');
            $table->jsonb('tags')->nullable()->comment('Теги для аналитики');
            $table->timestamps();

            $table->comment('Транзакции через платежные шлюзы');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
