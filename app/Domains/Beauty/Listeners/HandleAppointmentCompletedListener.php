<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\AppointmentCompleted;
use App\Domains\Beauty\Jobs\DeductConsumablesJob;
use App\Domains\Beauty\Jobs\ProcessAppointmentPaymentJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

/**
 * HandleAppointmentCompletedListener — CatVRF 2026.
 *
 * Диспатчит списание расходников и обработку оплаты при завершении записи.
 * Runs asynchronously via queue (ShouldQueue).
 * Maintains correlation_id chain.
 *
 * @package App\Domains\Beauty\Listeners
 */
final class HandleAppointmentCompletedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private Dispatcher $bus,
        private LoggerInterface $auditLogger,
    ) {}

    public function handle(AppointmentCompleted $event): void
    {
        $this->bus->dispatch(new DeductConsumablesJob(
            $event->appointmentId,
            $event->correlationId,
        ));

        $this->bus->dispatch(new ProcessAppointmentPaymentJob(
            $event->appointmentId,
            $event->correlationId,
        ));

        $this->auditLogger->info('AppointmentCompleted handled: jobs dispatched.', [
            'appointment_id' => $event->appointmentId,
            'master_id'      => $event->masterId,
            'payout_kopecks' => $event->payoutKopecks,
            'correlation_id' => $event->correlationId,
        ]);
    }

    public function failed(AppointmentCompleted $event, \Throwable $exception): void
    {
        $this->auditLogger->error('HandleAppointmentCompletedListener failed.', [
            'appointment_id' => $event->appointmentId,
            'error'          => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}
