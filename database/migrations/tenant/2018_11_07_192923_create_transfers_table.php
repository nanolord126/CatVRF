<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Создает таблицу transfers для Bavix Wallet системы.
     * Production 2026: idempotent, correlation_id, tags, индексы, документация.
     */
    public function up(): void
    {
        if (!Schema::hasTable('transfers')) {
            Schema::create('transfers', function (Blueprint $table) {
                $table->comment('Переводы между кошельками (Bavix Wallet). Отслеживает движение средств.');

                $table->bigIncrements('id')->comment('Уникальный ID передачи');
                $table->string('from_type')->comment('Тип отправителя (User, Organization)');
                $table->unsignedBigInteger('from_id')->comment('ID отправителя');
                $table->string('to_type')->comment('Тип получателя (User, Organization)');
                $table->unsignedBigInteger('to_id')->comment('ID получателя');
                $table->unsignedBigInteger('from_wallet_id')->comment('ID кошелька отправителя');
                $table->unsignedBigInteger('to_wallet_id')->comment('ID кошелька получателя');
                $table->decimal('amount', 64, 0)->comment('Сумма перевода');
                $table->string('status')->default('completed')->comment('Статус: completed, pending, failed');
                $table->timestamps()->comment('created_at, updated_at');
                
                // Traceability & Production 2026
                $table->string('correlation_id')->nullable()->index()->comment('Correlation ID для трассировки');
                $table->uuid('uuid')->nullable()->unique()->index()->comment('Уникальный UUID');
                $table->jsonb('meta')->nullable()->comment('JSON: дополнительные данные');
                $table->jsonb('tags')->nullable()->comment('Теги для категоризации переводов');
                
                // Индексы для оптимизации
                $table->index(['from_type', 'from_id'], 'transfers_from_type_id');
                $table->index(['to_type', 'to_id'], 'transfers_to_type_id');
                $table->index(['status', 'created_at'], 'transfers_status_created');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
