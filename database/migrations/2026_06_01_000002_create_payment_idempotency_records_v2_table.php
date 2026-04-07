<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица payment_idempotency_records_v2.
 * Используется Infrastructure/Models/IdempotencyModel.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_idempotency_records_v2')) {
            return;
        }

        Schema::create('payment_idempotency_records_v2', static function (Blueprint $table): void {
            $table->comment('Записи идемпотентности платежей (Clean Architecture v2)');

            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('operation', 64)->comment('Тип операции: payment.init, payment.refund и т.д.');
            $table->string('idempotency_key', 255)->comment('Ключ из заголовка X-Idempotency-Key');
            $table->string('payload_hash', 64)->comment('SHA-256 хэш тела запроса');
            $table->json('response_data')->nullable()->comment('Закэшированный ответ');
            $table->string('correlation_id', 36)->nullable()->index();
            $table->timestamp('expires_at')->nullable()->comment('TTL записи');
            $table->timestamps();

            // Уникальность: один ключ на тенант + операцию
            $table->unique(['tenant_id', 'operation', 'idempotency_key'], 'pir2_unique_key');
            $table->index(['tenant_id', 'expires_at'], 'pir2_tenant_expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_idempotency_records_v2');
    }
};


