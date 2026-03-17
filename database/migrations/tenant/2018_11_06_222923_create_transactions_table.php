<?php

declare(strict_types=1);

use Bavix\Wallet\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * Создает таблицу transactions для Bavix Wallet системы.
     * Production 2026: idempotent, correlation_id, tags, индексы, документация.
     */
    public function up(): void
    {
        if (!Schema::hasTable($this->table())) {
            Schema::create($this->table(), static function (Blueprint $table) {
                $table->comment('Транзакции кошельков (Bavix Wallet). Основной лог операций денежных средств.');

                $table->bigIncrements('id')->comment('Уникальный ID транзакции');
                $table->morphs('payable')->comment('Polymorphic: пользователь или организация');
                $table->unsignedBigInteger('wallet_id')->comment('ID кошелька');
                $table->enum('type', ['deposit', 'withdraw'])->index()->comment('Тип операции: пополнение или вывод');
                $table->decimal('amount', 64, 0)->comment('Сумма в минимальных единицах (копейки)');
                $table->boolean('confirmed')->comment('Подтверждена ли транзакция');
                $table->jsonb('meta')->nullable()->comment('JSON: дополнительные данные операции');
                $table->uuid('uuid')->unique()->comment('Уникальный UUID для публичного API');
                $table->timestamps()->comment('created_at, updated_at');

                // Traceability & Production 2026
                $table->string('correlation_id')->nullable()->index()->comment('Correlation ID для трассировки');
                $table->jsonb('tags')->nullable()->comment('Теги для классификации транзакций');

                // Индексы для оптимизации запросов
                $table->index(['payable_type', 'payable_id'], 'payable_type_payable_id_ind');
                $table->index(['payable_type', 'payable_id', 'type'], 'payable_type_ind');
                $table->index(['payable_type', 'payable_id', 'confirmed'], 'payable_confirmed_ind');
                $table->index(['payable_type', 'payable_id', 'type', 'confirmed'], 'payable_type_confirmed_ind');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return (new Transaction())->getTable();
    }
};
