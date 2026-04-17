<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица webhook_deliveries — журнал всех доставок webhook-событий.
 *
 * Хранит историю запросов: тело, заголовки, HTTP-статус ответа,
 * время выполнения, количество попыток (retry до 5 раз с экспоненциальным backoff).
 * Нужна для дашборда надёжности и дебага.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('webhook_endpoint_id')->constrained('webhook_endpoints')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->string('event_type')->index()->comment('payment.captured, order.created, fraud.blocked...');
            $table->json('payload')->comment('Тело события, отправленное на endpoint');
            $table->json('request_headers')->nullable()->comment('X-Signature, Content-Type и т.д.');
            $table->integer('http_status')->nullable()->comment('HTTP код ответа от получателя');
            $table->text('response_body')->nullable();
            $table->integer('duration_ms')->nullable()->comment('Время ответа в мс');
            $table->tinyInteger('attempt_number')->default(1)->comment('Номер попытки (1..5)');
            $table->tinyInteger('max_attempts')->default(5);
            $table->enum('status', ['pending', 'sent', 'success', 'failed', 'retrying'])->default('pending')->index();
            $table->timestamp('next_attempt_at')->nullable()->comment('Время следующей retry-попытки');
            $table->json('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['webhook_endpoint_id', 'created_at']);
            $table->index(['event_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
