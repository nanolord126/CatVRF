<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrow_holds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->unsignedBigInteger('payment_intent_id')->nullable()->index();
            $table->unsignedBigInteger('payment_transaction_id')->nullable()->index();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->bigInteger('amount_kopecks')->unsigned();
            $table->string('currency', 3)->default('RUB');
            $table->string('status')->default('held')->index(); // held, partially_released, released, canceled
            $table->json('release_conditions')->nullable(); // conditions for release
            $table->timestamp('auto_release_at')->nullable()->index();
            $table->bigInteger('released_amount_kopecks')->unsigned()->default(0);
            $table->bigInteger('remaining_amount_kopecks')->unsigned();
            $table->text('release_reason')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['wallet_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_holds');
    }
};
