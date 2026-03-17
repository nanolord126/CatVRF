<?php

declare(strict_types=1);

use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * Создает таблицу wallets для Bavix Wallet системы.
     * Production 2026: idempotent, correlation_id, tags, документация.
     */
    public function up(): void
    {
        if (!Schema::hasTable($this->table())) {
            Schema::create($this->table(), static function (Blueprint $table) {
                $table->comment('Кошельки пользователей/организаций (Bavix Wallet). Один пользователь может иметь несколько кошельков.');

                $table->bigIncrements('id')->comment('Уникальный ID кошелька');
                $table->morphs('holder')->comment('Polymorphic: владелец кошелька (user, organization)');
                $table->string('name')->comment('Название кошелька (напр., "основной", "бонусы")');
                $table->string('slug')->index()->comment('URL-safe идентификатор кошелька');
                $table->uuid('uuid')->unique()->comment('Уникальный UUID для публичного API');
                $table->string('description')->nullable()->comment('Описание кошелька');
                $table->jsonb('meta')->nullable()->comment('JSON: дополнительные данные кошелька');
                $table->decimal('balance', 64, 0)->default(0)->comment('Баланс в минимальных единицах');
                $table->unsignedSmallInteger('decimal_places')->default(2)->comment('Количество десятичных знаков');
                $table->timestamps()->comment('created_at, updated_at');

                // Traceability & Production 2026
                $table->string('correlation_id')->nullable()->index()->comment('Correlation ID для трассировки');
                $table->jsonb('tags')->nullable()->comment('Теги для категоризации кошельков');

                // Уникальные индексы
                $table->unique(['holder_type', 'holder_id', 'slug']);
            });
        }

        Schema::table($this->transactionTable(), function (Blueprint $table) {
            $table->foreign('wallet_id')
                ->references('id')
                ->on($this->table())
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return (new Wallet())->getTable();
    }

    private function transactionTable(): string
    {
        return (new Transaction())->getTable();
    }
};
