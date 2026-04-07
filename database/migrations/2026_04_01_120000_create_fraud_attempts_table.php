<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fraud_attempts')) {
            return;
        }

        Schema::create('fraud_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->string('transaction_id')->unique()->comment('ID транзакции из внешней системы');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->float('score')->comment('ML-скор от 0.0 до 1.0');
            $table->json('details')->comment('Полные данные запроса на проверку');
            $table->string('correlation_id')->nullable()->index();
            $table->jsonb('tags')->nullable();
            $table->timestamps();

            $table->comment('Записи о попытках мошеннических транзакций');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_attempts');
    }
};
