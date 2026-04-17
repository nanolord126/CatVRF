<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица webhook_endpoints — реестр подписок на исходящие webhooks.
 *
 * Каждая запись — URL, на который платформа отправляет события
 * (заказ создан, платёж получен, фрод-попытка и т.д.).
 * Подпись HMAC SHA-256 через WebhookSignatureService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_endpoints', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->string('url');
            $table->string('secret_hash', 64)->comment('SHA-256 от HMAC-secret для проверки подписи');
            $table->json('events')->comment('Список событий: ["order.created","payment.captured"]');
            $table->json('tags')->nullable()->comment('Произвольные метки (vertical, env и т.д.)');
            $table->enum('status', ['active', 'paused', 'disabled'])->default('active')->index();
            $table->integer('failure_count')->default(0)->comment('Счётчик подряд идущих ошибок');
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'business_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_endpoints');
    }
};
