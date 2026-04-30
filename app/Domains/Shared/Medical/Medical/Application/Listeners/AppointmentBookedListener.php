<?php

declare(strict_types=1);

namespace App\Domains\Shared\Medical\Application\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Shared\Medical\Application\Events\AppointmentBookedApplicationEvent;
use App\Domains\Shared\Medical\Application\Services\AppointmentNotificationService;
use App\Domains\Shared\Medical\Application\Services\CacheInvalidationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;

/**
 * Thin Listener: Delegates to Application Services
 * No business logic here, just orchestration
 */
final class AppointmentBookedListener implements ShouldQueue
{
    public string $queue = 'notification';

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly AppointmentNotificationService $notificationService,
        private readonly CacheInvalidationService $cacheInvalidationService,
        private readonly LogManager $log,) {}

    public function handle(AppointmentBookedApplicationEvent $event): void
    {
        $payload = $event->getPayload();

        try {
            // Delegate to Application Services
            $this->notificationService->sendAppointmentConfirmation(
                appointmentId: $payload['appointment_id'],
                patientId: $payload['patient_id'],
                doctorId: $payload['doctor_id'],
                scheduledAt: new \DateTimeImmutable($payload['scheduled_at']),
            );

            $this->cacheInvalidationService->invalidateAppointmentCache(
                appointmentId: $payload['appointment_id'],
                patientId: $payload['patient_id'],
                doctorId: $payload['doctor_id'],
            );

            $this->log->$this->logger->info('Appointment booked event processed', [
                'appointment_id' => $payload['appointment_id'],
                'correlation_id' => $event->getCorrelationId(),
            ]);

        } catch (\Throwable $e) {
            $this->log->error('Failed to process appointment booked event', [
                'appointment_id' => $payload['appointment_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function failed(AppointmentBookedApplicationEvent $event, \Throwable $exception): void
    {
        $this->log->critical('Appointment booked listener failed after retries', [
            'appointment_id' => $event->getPayload()['appointment_id'],
            'error' => $exception->getMessage(),
            'max_tries' => $this->tries,
        ]);
    }
}
