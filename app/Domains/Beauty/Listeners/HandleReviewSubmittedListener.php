<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\ReviewSubmitted;
use App\Domains\Beauty\Jobs\UpdateMasterRatingsJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

/**
 * HandleReviewSubmittedListener — CatVRF 2026.
 *
 * Запускает пересчёт рейтингов при новом отзыве.
 * Runs asynchronously via queue (ShouldQueue).
 * Maintains correlation_id chain.
 *
 * @package App\Domains\Beauty\Listeners
 */
final class HandleReviewSubmittedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private Dispatcher $bus,
        private CacheRepository $cache,
        private LoggerInterface $auditLogger,
    ) {}

    public function handle(ReviewSubmitted $event): void
    {
        $this->bus->dispatch(new UpdateMasterRatingsJob($event->correlationId));

        $this->cache->forget("master_reviews:{$event->masterId}");

        $this->auditLogger->info('ReviewSubmitted handled.', [
            'review_id'      => $event->reviewId,
            'master_id'      => $event->masterId,
            'client_id'      => $event->clientId,
            'rating'         => $event->rating,
            'correlation_id' => $event->correlationId,
        ]);
    }

    public function failed(ReviewSubmitted $event, \Throwable $exception): void
    {
        $this->auditLogger->error('HandleReviewSubmittedListener failed.', [
            'review_id'      => $event->reviewId,
            'error'          => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }

    /**
     * Определяет, нужно ли обрабатывать событие.
     */
    public function shouldQueue(ReviewSubmitted $event): bool
    {
        return $event->reviewId > 0;
    }

    /**
     * Очередь для обработки события.
     */
    public function viaQueue(): string
    {
        return 'beauty-events';
    }
}
