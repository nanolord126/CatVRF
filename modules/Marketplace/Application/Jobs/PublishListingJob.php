<?php

declare(strict_types=1);

namespace Modules\Marketplace\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Marketplace\Application\Services\MarketplaceAggregatorService;
use Ramsey\Uuid\UuidInterface;
use Psr\Log\LoggerInterface;

/**
 * Job для публикации позиции на маркетплейсе
 * Запускается асинхронно после создания/обновления
 */
final class PublishListingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly string $listingUuid,
    ) {}

    public function handle(
        MarketplaceAggregatorService $aggregator,
        LoggerInterface $logger,
    ): void {
        $logger->info('Starting listing publish job', [
            'listing_uuid' => $this->listingUuid,
        ]);

        try {
            $uuid = \Ramsey\Uuid\Uuid::fromString($this->listingUuid);
            $listing = $aggregator->publishListing($uuid);

            $logger->info('Listing publish job completed', [
                'listing_uuid' => $this->listingUuid,
                'status' => $listing->status->value,
            ]);
        } catch (\Throwable $e) {
            $logger->error('Listing publish job failed', [
                'error' => $e->getMessage(),
                'listing_uuid' => $this->listingUuid,
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        logger()->error('Listing publish job failed permanently', [
            'error' => $exception->getMessage(),
            'listing_uuid' => $this->listingUuid,
        ]);
    }
}
