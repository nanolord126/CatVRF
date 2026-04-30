<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\BeautyMasters\Infrastructure\Models\AppointmentModel;

final class SendReminder2h implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly int $appointmentId,
    ) {
    }

    public function handle(): void
    {
        try {
            $appointment = AppointmentModel::with(['user', 'master', 'service'])
                ->find($this->appointmentId);

            if (!$appointment) {
                Log::warning('Appointment not found for 2h reminder', ['appointment_id' => $this->appointmentId]);
                return;
            }

            if ($appointment->reminder_sent_2h > 0) {
                Log::info('2h reminder already sent', ['appointment_id' => $this->appointmentId]);
                return;
            }

            // TODO: Implement actual notification sending via Telegram/WhatsApp/SMS
            // Notification::send($appointment->user, new AppointmentReminder2hNotification($appointment));

            $appointment->update(['reminder_sent_2h' => $appointment->reminder_sent_2h + 1]);

            Log::info('2h reminder sent', [
                'appointment_id' => $this->appointmentId,
                'user_id' => $appointment->user_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send 2h reminder', [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
            ]);
            $this->release(60);
        }
    }
}
