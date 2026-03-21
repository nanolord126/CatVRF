<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CANON 2026: пересоздаёт payment_transactions с nullable payment_id и всеми нужными колонками.
 * SQLite не поддерживает ALTER COLUMN, поэтому пересоздаём через rename+create.
 * Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Только для SQLite (тесты)
        if (config('database.default') !== 'sqlite') {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->string('payment_id')->nullable()->change();
            });
            return;
        }

        // Удаляем возможный мусор от предыдущего неудачного запуска
        Schema::dropIfExists('payment_transactions_bak');

        Schema::rename('payment_transactions', 'payment_transactions_bak');

        // В SQLite при rename индексы сохраняют оригинальные имена — удаляем их
        foreach ([
            'payment_transactions_uuid_unique',
            'payment_transactions_idempotency_key_unique',
            'payment_transactions_tenant_id_index',
            'payment_transactions_user_id_index',
            'payment_transactions_status_index',
            'payment_transactions_correlation_id_index',
            'payment_transactions_payment_id_unique',
        ] as $idx) {
            try {
                DB::statement("DROP INDEX IF EXISTS \"{$idx}\"");
            } catch (\Throwable) {
                // ignore
            }
        }

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->nullable()->unique()->index();
            $table->string('payment_id')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('wallet_id')->nullable();
            $table->string('idempotency_key', 255)->nullable()->unique();
            $table->string('provider', 64)->nullable()->default('tinkoff');
            $table->string('provider_code', 64)->nullable();
            $table->string('provider_payment_id', 255)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 3)->nullable()->default('RUB');
            $table->string('status')->index()->default('pending');
            $table->string('payment_method', 64)->nullable()->default('bank_transfer');
            $table->boolean('hold')->default(false);
            $table->unsignedBigInteger('hold_amount')->default(0);
            $table->string('fiscal_number')->nullable();
            $table->string('fiscal_sign')->nullable();
            $table->string('receipt_url')->nullable();
            $table->string('qr_code')->nullable();
            $table->json('splits')->nullable();
            $table->decimal('fraud_score', 5, 4)->nullable()->default(0);
            $table->string('fraud_ml_version', 32)->nullable();
            $table->string('ml_fraud_version', 32)->nullable()->default('v1');
            $table->boolean('three_ds_required')->default(false);
            $table->boolean('three_ds_verified')->default(false);
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_fingerprint', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->json('meta')->nullable();
            $table->json('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
        });

        // Удаляем бэкап — тесты используют RefreshDatabase (migrate:fresh)
        Schema::dropIfExists('payment_transactions_bak');
    }

    public function down(): void
    {
        // no-op
    }
};
