<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('balance_transactions')) {
            return;
        }

        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index()->comment('Уникальный идентификатор транзакции');
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade')->comment('ID кошелька');
            $table->string('type')->comment('Тип транзакции (deposit, withdrawal, etc.)');
            $table->string('status')->default('completed')->comment('Статус транзакции');
            $table->bigInteger('amount')->comment('Сумма в копейках (может быть отрицательной)');
            $table->string('correlation_id')->nullable()->index()->comment('ID для сквозной трассировки');
            $table->jsonb('meta')->nullable()->comment('Дополнительные данные');
            $table->jsonb('tags')->nullable()->comment('Теги для аналитики');
            $table->timestamps();

            $table->comment('Транзакции по балансу кошельков (дебет/кредит)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_transactions');
    }
};
