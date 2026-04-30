<?php

declare(strict_types=1);

namespace App\Octane\Listeners;

use Psr\Log\LoggerInterface;

use Laravel\Octane\Events\WorkerStarting;
use App\Octane\Services\SwooleTableService;
use Illuminate\Log\LogManager;

final readonly class InitializeSwooleTables
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly SwooleTableService $tableService) {}

    public function handle(WorkerStarting $event): void
    {
        try {
            $this->tableService->initializeAllTables();
            $this->log->$this->logger->info('Swoole tables initialized successfully');
        } catch (\Throwable $e) {
            $this->log->error('Failed to initialize Swoole tables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
