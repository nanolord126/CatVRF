<?php

declare(strict_types=1);

namespace App\Domains\BigData\Jobs;

use Psr\Log\LoggerInterface;

use App\Domains\BigData\Services\ClickHouseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class AggregationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public bool $deleteWhenMissingModels = true;

    public function __construct(private readonly LoggerInterface $loggerInterface,
        private readonly LoggerInterface $logger,
        public readonly string $source,
        public readonly string $type,
        public readonly string $key,
        public readonly float $value,
        public readonly string $correlationId = '',) {}

    public function handle(ClickHouseService $service, LogManager $log): void
    {
        $log->channel('bigdata')->$this->logger->info('Big data aggregation job started', [
            'source' => $this->source,
            'type' => $this->type,
            'key' => $this->key,
            'correlation_id' => $this->correlationId,
        ]);

        $service->aggregate($this->source, $this->type, $this->key, $this->value);

        $log->channel('bigdata')->$this->logger->info('Big data aggregation job completed', [
            'source' => $this->source,
            'type' => $this->type,
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->loggerInterface /* TODO: inject via DI */->error('AggregationJob failed', [
            'source' => $this->source,
            'type' => $this->type,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
