<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->unsignedBigInteger('payment_transaction_id')->index();
            $table->unsignedBigInteger('payment_intent_id')->nullable()->index();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->bigInteger('amount_kopecks')->unsigned();
            $table->string('currency', 3)->default('RUB');
            $table->string('status')->default('pending')->index(); // pending, approved, processing, completed, failed
            $table->text('reason')->nullable();
            $table->string('reason_code')->nullable(); // customer_request, duplicate, fraudulent, etc.
            $table->string('provider')->nullable();
            $table->string('provider_refund_id')->nullable()->index();
            $table->string('refund_method')->nullable(); // original, alternative
            $table->bigInteger('processing_fee_kopecks')->unsigned()->default(0);
            $table->bigInteger('refunded_amount_kopecks')->unsigned()->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->string('requested_by_type')->nullable(); // user, admin, system
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['payment_transaction_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_transactions');
    }
};
