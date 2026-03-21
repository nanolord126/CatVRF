<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bavix Wallet
        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('correlation_id')->nullable()->index();
            $table->string('holder_type');
            $table->unsignedBigInteger('holder_id');
            $table->string('name')->default('default');
            $table->string('slug')->default('default');
            $table->decimal('balance', 20, 8)->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['holder_type', 'holder_id']);
            $table->unique(['holder_type', 'holder_id', 'slug']);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('correlation_id')->nullable()->index();
            $table->uuid('wallet_id');
            $table->string('type');
            $table->decimal('amount', 20, 8);
            $table->boolean('confirmed')->default(true);
            $table->json('meta')->nullable();
            $table->uuid('transaction_id')->nullable()->unique();
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
            $table->index(['wallet_id', 'type']);
        });

        Schema::create('transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('correlation_id')->nullable()->index();
            $table->uuid('from_wallet_id');
            $table->uuid('to_wallet_id');
            $table->uuid('from_transaction_id');
            $table->uuid('to_transaction_id');
            $table->decimal('amount', 20, 8);
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('from_wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
            $table->foreign('to_wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
