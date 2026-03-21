<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('balance_transactions');

        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Первичный ключ');
            $table->unsignedBigInteger('wallet_id')->index()->comment('ID кошелька');
            $table->string('tenant_id', 36)->index()->comment('ID тенанта (UUID)');
            $table->string('type', 50)->comment('Тип: deposit, withdrawal, commission, bonus, refund, payout, hold, release');
            $table->bigInteger('amount')->comment('Сумма (копейки, может быть отрицательной)');
            $table->bigInteger('balance_before')->default(0)->comment('Баланс до операции');
            $table->bigInteger('balance_after')->default(0)->comment('Баланс после операции');
            $table->string('status', 20)->default('completed')->comment('Статус: pending, completed, failed, cancelled');
            $table->string('source_type', 100)->nullable()->comment('Тип источника (order, appointment и т.д.)');
            $table->unsignedBigInteger('source_id')->nullable()->comment('ID источника');
            $table->string('correlation_id', 36)->nullable()->index()->comment('Correlation ID');
            $table->text('reason')->nullable()->comment('Причина операции');
            $table->json('tags')->nullable()->comment('Теги');
            $table->timestamps();

            $table->comment('Транзакции баланса кошельков (канон 2026)');
            $table->index(['wallet_id', 'type', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_transactions');
    }
};
