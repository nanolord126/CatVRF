<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Application\Services;

use Illuminate\Contracts\Mail\Mailer;

use Psr\Log\LoggerInterface;

use Illuminate\Log\LogManager;

/**
 * Application Service: Handles Beauty notifications
 */
final readonly class BeautyNotificationService
{
    public function __construct(private readonly Mailer $mailer,
        private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}
    public function sendAppointmentConfirmation(
        string $appointmentId,
        string $userId,
        string $salonId,
        string $masterId,
        \DateTimeImmutable $scheduledAt,
    ): void {
        try {
            // Send email notification
            // $this->mailer->to($user->email)->send(new AppointmentConfirmationMail($appointmentId));

            // Send SMS notification
            // SMS::send($user->phone, "Your beauty appointment at {$salonName} is confirmed for {$scheduledAt->format('Y-m-d H:i')}");

            // Send push notification
            // PushNotification::send($userId, [
            //     'title' => 'Appointment Confirmed',
            //     'body' => "Your beauty appointment is confirmed",
            // ]);

            $this->log->$this->logger->info('Beauty appointment confirmation sent', [
                'appointment_id' => $appointmentId,
                'user_id' => $userId,
                'salon_id' => $salonId,
                'master_id' => $masterId,
            ]);

        } catch (\Throwable $e) {
            $this->log->error('Failed to send beauty appointment confirmation', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function sendAppointmentReminder(string $appointmentId, string $userId, \DateTimeImmutable $scheduledAt): void
    {
        // Implementation for reminder notifications
    }

    public function sendAppointmentCancellation(string $appointmentId, string $userId, string $reason): void
    {
        // Implementation for cancellation notifications
    }
}
