<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class UpdateUserLatentFactorsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 300;
    public bool $deleteWhenMissingModels = true;

    public function __construct(private readonly LoggerInterface $loggerInterface,
        private readonly LoggerInterface $logger,
        public readonly int $userId,
        public readonly string $correlationId = '',) {}

    public function handle(LogManager $log): void
    {
        $log->channel('fashion')->$this->logger->info('User latent factors update started', [
            'user_id' => $this->userId,
            'correlation_id' => $this->correlationId,
        ]);

        // TODO: Implement user latent factor recalculation for recommender system
    }

    public function failed(\Throwable $exception): void
    {
        $this->loggerInterface /* TODO: inject via DI */->error('UpdateUserLatentFactorsJob failed', [
            'user_id' => $this->userId,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
