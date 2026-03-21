<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Пересоздаём таблицу wallets по канону 2026.
 * Старая структура (bavix/laravel-wallet) имела holder_type/holder_id/balance.
 * Канон требует tenant_id / current_balance / hold_amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Удаляем старую таблицу (bavix-структура или любая другая)
        Schema::dropIfExists('wallets');

        Schema::create('wallets', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Первичный ключ');
            $table->uuid('uuid')->unique()->nullable()->index()->comment('UUID кошелька');
            $table->string('tenant_id', 36)->index()->comment('ID тенанта (UUID)');
            $table->string('business_group_id', 36)->nullable()->index()->comment('ID филиала');
            $table->bigInteger('current_balance')->default(0)->comment('Текущий баланс (копейки)');
            $table->bigInteger('hold_amount')->default(0)->comment('Сумма на холде (копейки)');
            $table->bigInteger('cached_balance')->default(0)->comment('Кэшированный баланс (копейки)');
            $table->bigInteger('total_credited')->default(0)->comment('Всего пополнено');
            $table->bigInteger('total_debited')->default(0)->comment('Всего потрачено');
            $table->string('correlation_id', 36)->nullable()->index()->comment('Correlation ID');
            $table->json('tags')->nullable()->comment('Теги');
            $table->json('meta')->nullable()->comment('Метаданные');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Кошельки тенантов (канон 2026)');
            $table->index(['tenant_id', 'current_balance']);
            $table->index(['business_group_id', 'current_balance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
