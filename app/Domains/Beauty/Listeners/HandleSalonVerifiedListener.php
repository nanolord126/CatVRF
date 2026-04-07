<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\SalonVerified;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

/**
 * HandleSalonVerifiedListener
 *
 * Инвалидирует кэш салона при получении верификации.
 */
final class HandleSalonVerifiedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private CacheRepository $cache,
        private LoggerInterface $auditLogger,
    ) {}

    public function handle(SalonVerified $event): void
    {
        $this->cache->forget("salon:{$event->salonId}");
        $this->cache->forget("verified_salons:{$event->tenantId}");

        $this->auditLogger->info('SalonVerified handled: cache invalidated.', [
            'salon_id'       => $event->salonId,
            'tenant_id'      => $event->tenantId,
            'correlation_id' => $event->correlationId,
        ]);
    }

    public function failed(SalonVerified $event, \Throwable $exception): void
    {
        $this->auditLogger->error('HandleSalonVerifiedListener failed.', [
            'salon_id'       => $event->salonId,
            'error'          => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }

    /**
     * Определяет, нужно ли обрабатывать событие.
     */
    public function shouldQueue(SalonVerified $event): bool
    {
        return $event->salonId > 0;
    }

    /**
     * Очередь для обработки события.
     */
    public function viaQueue(): string
    {
        return 'beauty-events';
    }
}
