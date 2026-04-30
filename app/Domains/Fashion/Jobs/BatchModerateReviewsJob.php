<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class BatchModerateReviewsJob implements ShouldQueue
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
        public readonly array $reviewIds,
        public readonly string $correlationId = '',) {}

    public function handle(LogManager $log): void
    {
        $log->channel('fashion')->$this->logger->info('Batch review moderation started', [
            'review_count' => count($this->reviewIds),
            'correlation_id' => $this->correlationId,
        ]);

        // TODO: Implement AI-based batch review moderation
    }

    public function failed(\Throwable $exception): void
    {
        $this->loggerInterface /* TODO: inject via DI */->error('BatchModerateReviewsJob failed', [
            'review_count' => count($this->reviewIds),
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
