<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('product_type')->nullable();
            $table->bigInteger('amount_kopecks')->unsigned();
            $table->string('currency', 3)->default('RUB');
            $table->string('status')->default('incomplete')->index(); // incomplete, trialing, active, past_due, canceled, unpaid, incomplete_expired
            $table->string('interval')->default('month'); // day, week, month, year
            $table->integer('interval_count')->default(1);
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable()->index();
            $table->timestamp('trial_start')->nullable();
            $table->timestamp('trial_end')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_subscription_id')->nullable()->index();
            $table->timestamp('last_payment_at')->nullable();
            $table->timestamp('next_payment_at')->nullable()->index();
            $table->integer('failed_payment_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->string('correlation_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['product_id', 'product_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_subscriptions');
    }
};
