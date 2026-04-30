<?php

declare(strict_types=1);

namespace App\Domains\Payment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessGatewayPaymentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public bool $deleteWhenMissingModels = true;

    public function __construct(private readonly LoggerInterface $loggerInterface,
        private readonly LoggerInterface $logger,
        public readonly int $paymentId,
        public readonly string $gatewayProvider,
        public readonly string $correlationId = '',) {}

    public function handle(LogManager $log): void
    {
        $log->channel('payment')->$this->logger->info('Gateway payment processing started', [
            'payment_id' => $this->paymentId,
            'gateway_provider' => $this->gatewayProvider,
            'correlation_id' => $this->correlationId,
        ]);

        // TODO: Implement gateway-specific payment processing
    }

    public function failed(\Throwable $exception): void
    {
        $this->loggerInterface /* TODO: inject via DI */->error('ProcessGatewayPaymentJob failed', [
            'payment_id' => $this->paymentId,
            'gateway_provider' => $this->gatewayProvider,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
