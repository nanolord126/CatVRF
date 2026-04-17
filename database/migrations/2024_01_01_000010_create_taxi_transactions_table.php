<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ride_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('passenger_id')->nullable();
            $table->unsignedBigInteger('fleet_id')->nullable();
            $table->string('type');
            $table->integer('amount_kopeki');
            $table->string('currency', 3)->default('RUB');
            $table->string('status');
            $table->string('payment_method')->nullable();
            $table->string('payment_gateway')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->integer('commission_kopeki')->default(0);
            $table->integer('driver_payout_kopeki')->default(0);
            $table->integer('fleet_payout_kopeki')->default(0);
            $table->integer('platform_payout_kopeki')->default(0);
            $table->integer('refunded_amount_kopeki')->default(0);
            $table->text('refund_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'driver_id']);
            $table->index(['tenant_id', 'passenger_id']);
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_transactions');
    }
};
