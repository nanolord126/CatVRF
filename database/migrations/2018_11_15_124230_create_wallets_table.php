<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('wallets')) {
            return;
        }

        Schema::create('wallets', function (Blueprint $table) {
            $table->id()->comment('Первичный ключ');
            $table->uuid('uuid')->unique()->index()->comment('UUID кошелька');
            $table->unsignedBigInteger('tenant_id')->index()->comment('ID тенанта');
            $table->unsignedBigInteger('business_group_id')->nullable()->index()->comment('ID филиала (опционально)');
            $table->bigInteger('current_balance')->default(0)->comment('Текущий баланс (копейки)');
            $table->bigInteger('hold_amount')->default(0)->comment('Сумма на холде (копейки)');
            $table->bigInteger('total_credited')->default(0)->comment('Всего пополнено (копейки)');
            $table->bigInteger('total_debited')->default(0)->comment('Всего потрачено (копейки)');
            $table->string('correlation_id', 36)->nullable()->index()->comment('Correlation ID для трейсинга');
            $table->json('tags')->nullable()->comment('Теги для аналитики');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Кошельки пользователей и бизнесов (один на tenant/business_group)');
            $table->index(['tenant_id', 'current_balance', 'created_at']);
            $table->index(['business_group_id', 'current_balance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
