<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wallets')) {
            return;
        }

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index()->comment('Уникальный идентификатор кошелька');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->comment('ID тенанта');
            $table->unsignedBigInteger('business_group_id')->nullable()->comment('ID бизнес-группы (филиала)');
            $table->string('type')->comment('Тип кошелька (business, user)');
            $table->bigInteger('current_balance')->default(0)->comment('Текущий баланс в копейках');
            $table->bigInteger('hold_amount')->default(0)->comment('Сумма в холде в копейках');
            $table->string('correlation_id')->nullable()->index()->comment('ID для сквозной трассировки');
            $table->jsonb('tags')->nullable()->comment('Теги для аналитики и фильтрации');
            $table->timestamps();

            $table->comment('Кошельки для тенантов и бизнес-групп');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
