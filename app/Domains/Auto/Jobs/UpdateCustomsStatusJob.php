<?php

declare(strict_types=1);

namespace App\Domains\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class UpdateCustomsStatusJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;
    public bool $deleteWhenMissingModels = true;

    public function __construct(private readonly LoggerInterface $loggerInterface,
        private readonly LoggerInterface $logger,
        public readonly int $vehicleImportId,
        public readonly string $correlationId = '',) {}

    public function handle(LogManager $log): void
    {
        $log->channel('automotive')->$this->logger->info('Customs status update started', [
            'vehicle_import_id' => $this->vehicleImportId,
            'correlation_id' => $this->correlationId,
        ]);

        // TODO: Implement customs status sync with external API
    }

    public function failed(\Throwable $exception): void
    {
        $this->loggerInterface /* TODO: inject via DI */->error('UpdateCustomsStatusJob failed', [
            'vehicle_import_id' => $this->vehicleImportId,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
