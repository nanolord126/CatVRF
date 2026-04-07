<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\AppointmentScheduled;
use App\Domains\Beauty\Jobs\SendAppointmentRemindersJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

/**
 * SendAppointmentReminder — CatVRF 2026.
 *
 * Запускает джоб напоминания при планировании записи.
 * Runs asynchronously via queue (ShouldQueue).
 * Maintains correlation_id chain.
 *
 * @package App\Domains\Beauty\Listeners
 */
final class SendAppointmentReminder implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private Dispatcher $bus,
        private LoggerInterface $auditLogger,
    ) {}

    public function handle(AppointmentScheduled $event): void
    {
        $this->bus->dispatch(new SendAppointmentRemindersJob($event->correlationId));

        $this->auditLogger->info('Appointment reminder job dispatched.', [
            'appointment_id' => $event->appointmentId,
            'master_id'      => $event->masterId,
            'client_id'      => $event->clientId,
            'scheduled_at'   => $event->scheduledAt,
            'correlation_id' => $event->correlationId,
        ]);
    }

    public function failed(AppointmentScheduled $event, \Throwable $exception): void
    {
        $this->auditLogger->error('SendAppointmentReminder listener failed.', [
            'appointment_id' => $event->appointmentId,
            'error'          => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }

    /**
     * Определяет, нужно ли обрабатывать событие.
     */
    public function shouldQueue(AppointmentScheduled $event): bool
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
