<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Beauty\Events\SlotHeldEvent;
use App\Domains\Beauty\Services\BookingSlotHoldService;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;

final class ReleaseExpiredHoldListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 30;

    public int $delay = 900;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly BookingSlotHoldService $slotHoldService,
        private readonly LogManager $log,
        private readonly CacheManager $cache,) {}

    public function handle(SlotHeldEvent $event): void
    {
        try {
            $cacheKey = "slot_hold:{$event->slot->id}:{$event->correlationId}";

            if ($this->cache->has($cacheKey)) {
                $this->log->channel('audit')->$this->logger->info('beauty.slot.hold.already_processed', [
                    'correlation_id' => $event->correlationId,
                    'booking_slot_id' => $event->slot->id,
                ]);

                return;
            }

            $this->cache->put($cacheKey, true, 900);

            $this->slotHoldService->expireHeldSlots($event->slot->tenant_id);

            $this->log->channel('audit')->$this->logger->info('beauty.slot.hold.expiration.checked', [
                'correlation_id' => $event->correlationId,
                'tenant_id' => $event->slot->tenant_id,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('beauty.slot.hold.expiration.failed', [
                'correlation_id' => $event->correlationId,
                'booking_slot_id' => $event->slot->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(SlotHeldEvent $event, \Throwable $exception): void
    {
        $this->log->channel('audit')->error('beauty.slot.hold.expiration.queue.failed', [
            'correlation_id' => $event->correlationId,
            'booking_slot_id' => $event->slot->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
