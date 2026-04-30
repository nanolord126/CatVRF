<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Listeners;

use Modules\BeautyMasters\Domain\Events\AppointmentConfirmed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final readonly class SendAppointmentConfirmation
{
    public function handle(AppointmentConfirmed $event): void
    {
        try {
            $appointment = \Modules\BeautyMasters\Infrastructure\Models\AppointmentModel::with(['master', 'user'])
                ->find($event->appointmentId);

            if (!$appointment) {
                Log::warning('Appointment not found for confirmation', ['appointment_id' => $event->appointmentId]);
                return;
            }

            // TODO: Implement notification sending via Telegram/WhatsApp/SMS
            // Notification::send($appointment->user, new AppointmentConfirmedNotification($appointment));

            Log::info('Appointment confirmation sent', [
                'appointment_id' => $event->appointmentId,
                'user_id' => $event->userId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send appointment confirmation', [
                'appointment_id' => $event->appointmentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
