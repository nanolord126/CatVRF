<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('balance_transactions')) {
            return;
        }

        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->enum('type', ['deposit', 'withdrawal', 'commission', 'bonus', 'refund', 'payout', 'hold', 'release']);
            $table->bigInteger('amount')->comment('Сумма в копейках (может быть отрицательной)');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->text('reason')->nullable()->comment('Причина транзакции');
            $table->string('source_type', 64)->nullable()->comment('Тип источника (order, appointment, payment, refund, etc.)');
            $table->unsignedBigInteger('source_id')->nullable()->index()->comment('ID источника');
            $table->string('correlation_id', 36)->nullable()->index();
            $table->bigInteger('balance_before')->nullable()->comment('Баланс до транзакции');
            $table->bigInteger('balance_after')->nullable()->comment('Баланс после транзакции');
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->comment('Журнал всех операций с балансом (дебет/кредит)');
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
            $table->index(['tenant_id', 'type', 'created_at']);
            $table->index(['wallet_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_transactions');
    }
};
