<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_driver_wallets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('driver_id')->unique();
            $table->integer('balance_kopeki')->default(0);
            $table->integer('frozen_kopeki')->default(0);
            $table->integer('total_earned_kopeki')->default(0);
            $table->integer('total_withdrawn_kopeki')->default(0);
            $table->string('currency', 3)->default('RUB');
            $table->string('status')->default('active');
            $table->boolean('is_verified')->default(true);
            $table->unsignedBigInteger('verification_document_id')->nullable();
            $table->timestamp('last_withdrawal_at')->nullable();
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'driver_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_driver_wallets');
    }
};
