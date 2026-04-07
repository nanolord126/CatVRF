<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\MasterRatingUpdated;
use App\Domains\Beauty\Jobs\RecalculateSalonRatingJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

/**
 * HandleMasterRatingUpdatedListener — CatVRF 2026.
 *
 * Инвалидирует кэш рейтинга мастера и запускает пересчёт рейтинга салона.
 * Runs asynchronously via queue (ShouldQueue).
 * Maintains correlation_id chain.
 *
 * @package App\Domains\Beauty\Listeners
 */
final class HandleMasterRatingUpdatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private Dispatcher $bus,
        private CacheRepository $cache,
        private LoggerInterface $auditLogger,
    ) {}

    public function handle(MasterRatingUpdated $event): void
    {
        $this->cache->forget("master_rating:{$event->masterId}");
        $this->cache->forget("master_reviews:{$event->masterId}");

        $this->bus->dispatch(new RecalculateSalonRatingJob($event->correlationId));

        $this->auditLogger->info('MasterRatingUpdated handled.', [
            'master_id'      => $event->masterId,
            'old_rating'     => $event->oldRating,
            'new_rating'     => $event->newRating,
            'correlation_id' => $event->correlationId,
        ]);
    }

    public function failed(MasterRatingUpdated $event, \Throwable $exception): void
    {
        $this->auditLogger->error('HandleMasterRatingUpdatedListener failed.', [
            'master_id'      => $event->masterId,
            'error'          => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }

    /**
     * Определяет, нужно ли обрабатывать событие.
     */
    public function shouldQueue(MasterRatingUpdated $event): bool
    {
        return $event->masterId > 0;
    }

    /**
     * Очередь для обработки события.
     */
    public function viaQueue(): string
    {
        return 'beauty-events';
    }
}
