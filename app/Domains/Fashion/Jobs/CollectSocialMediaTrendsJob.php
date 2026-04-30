<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CollectSocialMediaTrendsJob implements ShouldQueue
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
        public readonly int $tenantId,
        public readonly string $platform = 'instagram',
        public readonly string $correlationId = '',) {}

    public function handle(LogManager $log): void
    {
        $log->channel('fashion')->$this->logger->info('Social media trend collection started', [
            'tenant_id' => $this->tenantId,
            'platform' => $this->platform,
            'correlation_id' => $this->correlationId,
        ]);

        // TODO: Implement social media trend collection via API
    }

    public function failed(\Throwable $exception): void
    {
        $this->loggerInterface /* TODO: inject via DI */->error('CollectSocialMediaTrendsJob failed', [
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
