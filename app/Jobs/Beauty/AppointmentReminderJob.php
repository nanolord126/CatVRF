<?php declare(strict_types=1);

namespace App\Jobs\Beauty;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppointmentReminderJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            private readonly Appointment $appointment,
            private readonly string $type = 'customer', // 'customer' or 'master'
            private readonly string $correlationId
        ) {
            $this->onQueue('beauty_notifications');
        }

        public function tags(): array
        {
            return ['beauty', 'reminder', 'appointment:' . $this->appointment->id];
        }

        public function handle(): void
        {
            Log::channel('audit')->info('Job Started: Send Appointment Reminder', [
                'appointment_id' => $this->appointment->id,
                'type' => $this->type,
                'correlation_id' => $this->correlationId
            ]);

            try {
                // Реализация отправки через Mail или SMS
                // (В каноне здесь будет вызов NotificationService)

                // Log successful reminder
                Log::channel('audit')->info('Job Finished: Appointment Reminder Sent', [
                    'appointment_id' => $this->appointment->id,
                    'type' => $this->type,
                    'correlation_id' => $this->correlationId
                ]);

            } catch (\Throwable $e) {
                Log::channel('audit')->error('Job Failed: Appointment Reminder Error', [
                    'appointment_id' => $this->appointment->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId
                ]);

                throw $e;
            }
        }
}
