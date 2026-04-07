<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\AppointmentCancelled;
use App\Domains\Beauty\Jobs\NotifyLowConsumablesJob;
use App\Services\InventoryManagementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

/**
 * HandleAppointmentCancelledListener
 *
 * Освобождает hold расходников при отмене записи.
 */
final class HandleAppointmentCancelledListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private InventoryManagementService $inventory,
        private LoggerInterface            $auditLogger,
    ) {}

    public function handle(AppointmentCancelled $event): void
    {
        $this->inventory->releaseStock(
            $event->appointmentId,
            'appointment',
            $event->appointmentId,
        );

        $this->auditLogger->info('AppointmentCancelled handled: inventory released.', [
            'appointment_id' => $event->appointmentId,
            'client_id'      => $event->clientId,
            'reason'         => $event->reason,
            'correlation_id' => $event->correlationId,
        ]);
    }

    public function failed(AppointmentCancelled $event, \Throwable $exception): void
    {
        $this->auditLogger->error('HandleAppointmentCancelledListener failed.', [
            'appointment_id' => $event->appointmentId,
            'error'          => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }

    /**
     * Определяет, нужно ли обрабатывать событие.
     */
    public function shouldQueue(AppointmentCancelled $event): bool
    {
        return $event->appointmentId > 0;
    }

    /**
     * Очередь для обработки события.
     */
    public function viaQueue(): string
    {
        return 'beauty-events';
    }
}
