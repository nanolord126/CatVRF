<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\SlotReleasedEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;

final readonly class SendSlotReleasedNotificationListener implements ShouldQueue
{
    public int $delay = 5;

    public function __construct(
        private NotificationService $notificationService,
        private Logger $logger,
    ) {
        $this->onQueue('beauty-notifications');
    }

    public function handle(SlotReleasedEvent $event): void
    {
        $this->logger->channel('audit')->info('beauty.listener.slot_released.start', [
            'correlation_id' => $event->correlationId,
            'booking_slot_id' => $event->slot->id,
            'reason' => $event->reason,
        ]);

        try {
            if ($event->slot->customer_id === null) {
                $this->logger->channel('audit')->warning('beauty.listener.slot_released.no_customer', [
                    'correlation_id' => $event->correlationId,
                    'booking_slot_id' => $event->slot->id,
                ]);
                return;
            }

            $title = match ($event->reason) {
                'payment_failed' => 'Оплата не прошла',
                'expired' => 'Время резерва истекло',
                'cancelled' => 'Запись отменена',
                default => 'Слот освобождён',
            };

            $message = match ($event->reason) {
                'payment_failed' => sprintf(
                    'К сожалению, оплата не прошла. Слот на %s %s освобождён. Попробуйте забронировать снова.',
                    $event->slot->slot_date->format('d.m.Y'),
                    $event->slot->slot_time->format('H:i'),
                ),
                'expired' => sprintf(
                    'Время резерва слота на %s %s истекло. Слот освобождён.',
                    $event->slot->slot_date->format('d.m.Y'),
                    $event->slot->slot_time->format('H:i'),
                ),
                'cancelled' => sprintf(
                    'Запись на %s %s отменена.',
                    $event->slot->slot_date->format('d.m.Y'),
                    $event->slot->slot_time->format('H:i'),
                ),
                default => sprintf(
                    'Слот на %s %s освобождён. Причина: %s',
                    $event->slot->slot_date->format('d.m.Y'),
                    $event->slot->slot_time->format('H:i'),
                    $event->reason,
                ),
            };

            $this->notificationService->send(
                userId: $event->slot->customer_id,
                type: 'slot_released',
                title: $title,
                message: $message,
                data: [
                    'booking_slot_id' => $event->slot->id,
                    'slot_date' => $event->slot->slot_date->toIso8601String(),
                    'slot_time' => $event->slot->slot_time->toIso8601String(),
                    'reason' => $event->reason,
                    'correlation_id' => $event->correlationId,
                ],
            );

            $this->logger->channel('audit')->info('beauty.listener.slot_released.success', [
                'correlation_id' => $event->correlationId,
                'booking_slot_id' => $event->slot->id,
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('beauty.listener.slot_released.failed', [
                'correlation_id' => $event->correlationId,
                'booking_slot_id' => $event->slot->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
