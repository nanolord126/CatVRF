<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Создает таблицы для платежей и интеграций с кошельками.
     * Production 2026: idempotent, платежи, холдирование, статусы.
     */
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->comment('Платежи: заказы, инвойсы, расчеты.');
                
                $table->id();
                $table->uuid('uuid')->unique()->comment('UUID платежа');
                $table->string('type')->comment('Тип: invoice, payment, refund');
                $table->string('status')->default('pending')->index()->comment('Статус платежа');
                $table->decimal('amount', 15, 2)->comment('Сумма платежа');
                $table->string('currency', 3)->default('RUB')->comment('Валюта');
                $table->unsignedBigInteger('wallet_id')->nullable()->comment('ID кошелька получателя');
                $table->string('external_id')->nullable()->unique()->comment('ID платежа в платежной системе');
                $table->jsonb('metadata')->nullable()->comment('JSON: данные платежа');
                $table->timestamps();
                
                $table->string('correlation_id')->nullable()->index()->comment('Correlation ID');
                $table->jsonb('tags')->nullable()->comment('Теги платежа');
                
                $table->index(['type', 'status', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
