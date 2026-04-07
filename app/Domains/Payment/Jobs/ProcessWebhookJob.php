<?php

declare(strict_types=1);

namespace App\Domains\Payment\Jobs;

use App\Domains\Payment\Enums\PaymentProvider;
use App\Domains\Payment\Services\PaymentCoordinatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Асинхронная обработка тяжелой логики вебхука.
 * Извлекает нужный провайдер, валидирует, изменяет статусы.
 */
final class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Повторные попытки при ошибках (сбои БД, дэдлоки).
     */
    public int $tries = 3;

    /**
     * Максимальное количество исключений.
     */
    public int $maxExceptions = 2;

    /**
     * @param PaymentProvider $provider
     * @param array<string, mixed> $payload
     * @param string $signature
     * @param string $correlationId
     */
    public function __construct(
        public readonly PaymentProvider $provider,
        public readonly array $payload,
        public readonly string $signature,
        public readonly string $correlationId,
    ) {}

    /**
     * Выполнение джоба.
     */
    public function handle(
        \App\Domains\Payment\Services\PaymentCoordinatorService $coordinator,
        \App\Domains\Payment\Services\Gateways\PaymentGatewayFactory $factory,
    ): void {
        try {
            $gateway = $factory->make($this->provider);

            $coordinator->handleWebhook(
                gateway: $gateway,
                payload: $this->payload,
                signature: $this->signature,
                correlationId: $this->correlationId,
            );
        } catch (\Throwable $e) {
            // Re-throw to retry if needed.
            // Transaction inside coordinator handles atomicity.
            throw $e;
        }
    }

    /**
     * Вызывается при провале джоба (после всех попыток).
     */
    public function failed(\Throwable $e): void
    {
        report(new \RuntimeException(
            "ProcessWebhookJob failed [provider={$this->provider->value}] [correlation_id={$this->correlationId}]: {$e->getMessage()}",
            previous: $e,
        ));
    }

    /**
     * Теги для Horizon мониторинга.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'payment_webhook_job',
            'provider:' . $this->provider->value,
        ];
    }
}
