<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\SlotHeldEvent;
use App\Domains\Beauty\Services\BookingSlotHoldService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

final class ReleaseExpiredHoldListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $timeout = 30;
    public int $delay = 900;

    public function __construct(
        private BookingSlotHoldService $slotHoldService,
    ) {}

    public function handle(SlotHeldEvent $event): void
    {
        try {
            $cacheKey = "slot_hold:{$event->slot->id}:{$event->correlationId}";
            
            if (Cache::has($cacheKey)) {
                Log::channel('audit')->info('beauty.slot.hold.already_processed', [
                    'correlation_id' => $event->correlationId,
                    'booking_slot_id' => $event->slot->id,
                ]);
                return;
            }

            Cache::put($cacheKey, true, 900);

            $this->slotHoldService->expireHeldSlots($event->slot->tenant_id);

            Log::channel('audit')->info('beauty.slot.hold.expiration.checked', [
                'correlation_id' => $event->correlationId,
                'tenant_id' => $event->slot->tenant_id,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('beauty.slot.hold.expiration.failed', [
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
        Log::channel('audit')->error('beauty.slot.hold.expiration.queue.failed', [
            'correlation_id' => $event->correlationId,
            'booking_slot_id' => $event->slot->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
