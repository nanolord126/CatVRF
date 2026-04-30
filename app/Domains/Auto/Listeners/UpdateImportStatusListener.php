<?php

declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Auto\Events\CarImportDutiesPaidEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;

final class UpdateImportStatusListener implements ShouldQueue
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}
    public function handle(CarImportDutiesPaidEvent $event): void
    {
        $this->log->channel('audit')->$this->logger->info('car.import.duties.paid.listener', [
            'import_id' => $event->importId,
            'vin' => $event->vin,
            'paid_amount' => $event->paidAmount,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
