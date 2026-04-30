<?php

declare(strict_types=1);

namespace App\Domains\Shared\Medical\Application\Services;

use Psr\Log\LoggerInterface;

use Illuminate\Log\LogManager;

/**
 * Application Service: Handles appointment notifications
 * Contains business logic for notification processing
 */
final readonly class AppointmentNotificationService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    public function sendAppointmentConfirmation(
        string $appointmentId,
        string $patientId,
        string $doctorId,
        \DateTimeImmutable $scheduledAt,
    ): void {
        try {
            // Send email notification
            // $this->sendEmail($patientId, $appointmentId, $scheduledAt);

            // Send SMS notification
            // $this->sendSms($patientId, $appointmentId, $scheduledAt);

            // Send push notification
            // $this->sendPush($patientId, $appointmentId, $scheduledAt);

            $this->log->$this->logger->info('Appointment confirmation sent', [
                'appointment_id' => $appointmentId,
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
            ]);

        } catch (\Throwable $e) {
            $this->log->error('Failed to send appointment confirmation', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function sendAppointmentReminder(
        string $appointmentId,
        string $patientId,
        \DateTimeImmutable $scheduledAt,
    ): void {
        // Implementation for reminder notifications
    }

    public function sendAppointmentCancellation(
        string $appointmentId,
        string $patientId,
        string $reason,
    ): void {
        // Implementation for cancellation notifications
    }
}
