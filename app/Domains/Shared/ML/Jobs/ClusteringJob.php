<?php

declare(strict_types=1);

namespace App\Domains\ML\Jobs;

use Psr\Log\LoggerInterface;

use Illuminate\Support\Str;

use App\Domains\ML\Services\ClusteringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;

final class ClusteringJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly ClusteringService $clusteringService,
    ,
        public readonly string $correlationId = '') {}

    public function onQueue(): string
    {
        return 'ml';
    }

    public function handle(LogManager $log): void
    {
        $correlationId = $this->correlationId ?: (string) Str::uuid();
        $this->clusteringService->runClustering();
        $log->channel('audit')->$this->logger->info('ML clustering job completed');
    }
}
