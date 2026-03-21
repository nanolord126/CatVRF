<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_idempotency_records')) {
            return;
        }

        Schema::create('payment_idempotency_records', static function (Blueprint $table): void {
            $table->comment('Idempotency records for payment operations — prevents double charges');

            $table->id();
            $table->string('operation', 100)->comment('Operation type: payment_init, refund, payout, card_bind');
            $table->string('idempotency_key', 255)->comment('Client-supplied idempotency key');
            $table->string('merchant_id', 100)->nullable()->comment('Merchant or tenant identifier');
            $table->string('payload_hash', 64)->comment('SHA-256 hash of the request payload');
            $table->json('response_data')->nullable()->comment('Cached response to replay on duplicate request');
            $table->string('status', 50)->default('pending')->comment('pending, completed, failed');
            $table->string('correlation_id', 36)->nullable()->index()->comment('Correlation ID for audit trail');
            $table->timestamp('expires_at')->index()->comment('When this idempotency record can be purged');
            $table->timestamps();

            $table->unique(['operation', 'idempotency_key', 'merchant_id'], 'uq_idempotency_key');
            $table->index(['payload_hash'], 'idx_payload_hash');
            $table->index(['expires_at', 'status'], 'idx_expires_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_idempotency_records');
    }
};
