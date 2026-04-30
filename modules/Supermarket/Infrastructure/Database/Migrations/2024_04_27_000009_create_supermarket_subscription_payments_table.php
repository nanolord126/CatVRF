<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('supermarket_subscriptions')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('supermarket_orders')->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending');
            $table->enum('payment_method', ['card', 'sbp', 'invoice'])->default('card');
            $table->string('gateway_transaction_id')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['subscription_id', 'status']);
            $table->index(['status', 'next_attempt_at']);
            $table->index('gateway_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_subscription_payments');
    }
};
