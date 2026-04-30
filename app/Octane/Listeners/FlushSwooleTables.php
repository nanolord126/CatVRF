<?php

declare(strict_types=1);

namespace App\Octane\Listeners;

use Psr\Log\LoggerInterface;

use Laravel\Octane\Events\WorkerStopping;
use App\Octane\Services\SwooleTableService;
use Illuminate\Log\LogManager;

final readonly class FlushSwooleTables
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly SwooleTableService $tableService) {}

    public function handle(WorkerStopping $event): void
    {
        try {
            // Persist critical data before shutdown
            $this->tableService->persistCriticalData();

            $this->log->$this->logger->info('Swoole tables flushed successfully');
        } catch (\Throwable $e) {
            $this->log->error('Failed to flush Swoole tables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Non-critical during shutdown
        }
    }
}
