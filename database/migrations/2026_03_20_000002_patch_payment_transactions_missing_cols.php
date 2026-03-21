<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CANON 2026: добавляет недостающие колонки в payment_transactions.
 * Idempotent — каждая колонка проверяется через hasColumn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_transactions', 'uuid')) {
                $table->string('uuid', 36)->nullable()->unique()->index()->after('id');
            }
            if (! Schema::hasColumn('payment_transactions', 'payment_method')) {
                $table->string('payment_method', 64)->nullable()->default('bank_transfer');
            }
            if (! Schema::hasColumn('payment_transactions', 'ml_fraud_version')) {
                $table->string('ml_fraud_version', 32)->nullable()->default('v1');
            }
            if (! Schema::hasColumn('payment_transactions', 'meta')) {
                $table->json('meta')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            // no-op: do not drop in down — data may exist
        });
    }
};
