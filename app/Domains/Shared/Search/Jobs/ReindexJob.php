<?php

declare(strict_types=1);

namespace App\Domains\Search\Jobs;

use Psr\Log\LoggerInterface;

use Illuminate\Support\Str;

use App\Domains\Search\Services\SearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;

final class ReindexJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly string $type,
    ,
        public readonly string $correlationId = '') {}

    public function onQueue(): string
    {
        return 'search';
    }

    public function handle(SearchService $service, LogManager $log): void
    {
        $correlationId = $this->correlationId ?: (string) Str::uuid();
        $service->rebuild($this->type);
        $log->channel('audit')->$this->logger->info('Search reindex job completed', [
            'type' => $this->type,
        ]);
    }
}
