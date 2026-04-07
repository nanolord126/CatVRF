<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\AppointmentConfirmed;
use App\Domains\Beauty\Jobs\SendAppointmentRemindersJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

/**
 * HandleAppointmentConfirmedListener — CatVRF 2026.
 *
 * Планирует напоминание клиенту за 24 ч до записи.
 * Runs asynchronously via queue (ShouldQueue).
 * Maintains correlation_id chain.
 *
 * @package App\Domains\Beauty\Listeners
 */
final class HandleAppointmentConfirmedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private Dispatcher $bus,
        private LoggerInterface $auditLogger,
    ) {}

    public function handle(AppointmentConfirmed $event): void
    {
        $this->bus->dispatch(new SendAppointmentRemindersJob($event->correlationId));

        $this->auditLogger->info('AppointmentConfirmed handled: reminder dispatched.', [
            'appointment_id' => $event->appointmentId,
            'client_id'      => $event->clientId,
            'master_id'      => $event->masterId,
            'correlation_id' => $event->correlationId,
        ]);
    }

    public function failed(AppointmentConfirmed $event, \Throwable $exception): void
    {
        $this->auditLogger->error('HandleAppointmentConfirmedListener failed.', [
            'appointment_id' => $event->appointmentId,
            'error'          => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }

    /**
     * Определяет, нужно ли обрабатывать событие.
     */
    public function shouldQueue(AppointmentConfirmed $event): bool
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
