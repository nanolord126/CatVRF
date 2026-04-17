<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\SlotHeldEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;

final readonly class SendSlotHeldNotificationListener implements ShouldQueue
{
    public int $delay = 5;

    public function __construct(
        private NotificationService $notificationService,
        private Logger $logger,
    ) {
        $this->onQueue('beauty-notifications');
    }

    public function handle(SlotHeldEvent $event): void
    {
        $this->logger->channel('audit')->info('beauty.listener.slot_held.start', [
            'correlation_id' => $event->correlationId,
            'booking_slot_id' => $event->slot->id,
            'customer_id' => $event->slot->customer_id,
        ]);

        try {
            $this->notificationService->send(
                userId: $event->slot->customer_id,
                type: 'slot_held',
                title: 'Слот зарезервирован',
                message: sprintf(
                    'Ваш слот на %s %s зарезервирован. Оплата должна быть произведена до %s',
                    $event->slot->slot_date->format('d.m.Y'),
                    $event->slot->slot_time->format('H:i'),
                    $event->slot->expires_at->format('H:i'),
                ),
                data: [
                    'booking_slot_id' => $event->slot->id,
                    'slot_date' => $event->slot->slot_date->toIso8601String(),
                    'slot_time' => $event->slot->slot_time->toIso8601String(),
                    'expires_at' => $event->slot->expires_at->toIso8601String(),
                    'correlation_id' => $event->correlationId,
                ],
            );

            $this->logger->channel('audit')->info('beauty.listener.slot_held.success', [
                'correlation_id' => $event->correlationId,
                'booking_slot_id' => $event->slot->id,
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('beauty.listener.slot_held.failed', [
                'correlation_id' => $event->correlationId,
                'booking_slot_id' => $event->slot->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
