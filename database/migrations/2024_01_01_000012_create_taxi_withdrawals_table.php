<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('wallet_id');
            $table->unsignedBigInteger('driver_id');
            $table->integer('amount_kopeki');
            $table->string('currency', 3)->default('RUB');
            $table->string('status')->default('pending');
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number');
            $table->string('bank_account_holder');
            $table->string('bic')->nullable();
            $table->string('inn')->nullable();
            $table->string('kpp')->nullable();
            $table->integer('processing_fee_kopeki')->default(0);
            $table->integer('net_amount_kopeki');
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'wallet_id']);
            $table->index(['tenant_id', 'driver_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_withdrawals');
    }
};
