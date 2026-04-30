<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\VeganProducts\Jobs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Client\Factory as HttpClientFactory;

/**
 * VeganInventorySyncJob - Sync inventory with external suppliers.
 */
final class VeganInventorySyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpClientFactory $http,
        private readonly int $storeId,
        private readonly string $correlationId,
    ) {}

    public function tags(): array
    {
        return ['veganproducts', 'job'];
    }

    public function handle(): void
    {
        $this->logger->info('LAYER-8: Vegan Inventory Sync START', [
            'store' => $this->storeId,
            'correlation_id' => $this->correlationId,
        ]);

        // Mock sync logic
        // $this->http->get('https://supplier.api/sync?store=' . $this->storeId);

        $this->logger->info('LAYER-8: Vegan Inventory Sync COMPLETE', [
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->error('veganproducts job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}