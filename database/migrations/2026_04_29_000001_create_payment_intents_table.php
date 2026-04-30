<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('payable_type')->nullable(); // Order, Subscription, etc.
            $table->unsignedBigInteger('payable_id')->nullable();
            $table->bigInteger('amount_kopecks')->unsigned();
            $table->string('currency', 3)->default('RUB');
            $table->string('status')->default('pending')->index(); // pending, processing, succeeded, failed, canceled, requires_action
            $table->string('payment_method')->nullable();
            $table->string('payment_method_provider')->nullable();
            $table->string('provider')->nullable(); // tinkoff, tochka, sber
            $table->string('provider_payment_intent_id')->nullable()->index();
            $table->boolean('capture_method')->default(true); // true = automatic, false = manual (hold)
            $table->string('confirmation_url')->nullable();
            $table->string('payment_url')->nullable();
            $table->string('client_secret')->nullable();
            $table->boolean('requires_action')->default(false);
            $table->string('next_action')->nullable(); // 3ds, redirect, etc.
            $table->text('description')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('return_url')->nullable();
            $table->string('cancel_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->decimal('fraud_score', 5, 2)->nullable();
            $table->string('fraud_decision')->nullable(); // allow, block, review
            $table->timestamps();

            $table->index(['payable_type', 'payable_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
