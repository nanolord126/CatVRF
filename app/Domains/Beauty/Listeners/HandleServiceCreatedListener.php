<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;


use App\Services\FraudControlService;
use App\Domains\Beauty\Events\ServiceCreated;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

/**
 * HandleServiceCreatedListener — CatVRF 2026.
 *
 * Инвалидирует кэш услуг салона при создании новой услуги.
 * Runs asynchronously via queue (ShouldQueue).
 * Maintains correlation_id chain.
 *
 * @package App\Domains\Beauty\Listeners
 */
final class HandleServiceCreatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private FraudControlService $fraud,
        private CacheRepository $cache,
        private LoggerInterface $auditLogger,
    ) {}

    public function handle(ServiceCreated $event): void
    {
        $this->cache->forget("salon_services:{$event->salonId}");

        $this->auditLogger->info('ServiceCreated handled: cache invalidated.', [
            'service_id'     => $event->serviceId,
            'salon_id'       => $event->salonId,
            'price_kopecks'  => $event->priceKopecks,
            'correlation_id' => $event->correlationId,
        ]);
    }

    public function failed(ServiceCreated $event, \Throwable $exception): void
    {
        $this->auditLogger->error('HandleServiceCreatedListener failed.', [
            'service_id'     => $event->serviceId,
            'error'          => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }

    /**
     * Определяет, нужно ли обрабатывать событие.
     */
    public function shouldQueue(ServiceCreated $event): bool
    {
        return $event->serviceId > 0;
    }

    /**
     * Очередь для обработки события.
     */
    public function viaQueue(): string
    {
        return 'beauty-events';
    }
}
