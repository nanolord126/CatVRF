<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->string('provider')->index(); // tinkoff, tochka, sber
            $table->string('status')->default('pending')->index(); // pending, processing, completed, failed
            $table->bigInteger('total_amount_kopecks')->unsigned();
            $table->integer('total_count')->unsigned();
            $table->integer('processed_count')->unsigned()->default(0);
            $table->integer('failed_count')->unsigned()->default(0);
            $table->string('currency', 3)->default('RUB');
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('provider_batch_id')->nullable()->index();
            $table->json('provider_response')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_batches');
    }
};
