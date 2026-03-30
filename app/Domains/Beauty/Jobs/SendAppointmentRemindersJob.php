<?php declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SendAppointmentRemindersJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithQueue;
        use Queueable;
        use SerializesModels;

        public function __construct(
            private readonly string $correlationId,
        ) {}

        public function handle(): void
        {
            $appointments = Appointment::query()
                ->where('status', 'confirmed')
                ->whereBetween('datetime_start', [now(), now()->addHours(24)])
                ->with(['client', 'master', 'service', 'salon'])
                ->get();

            foreach ($appointments as $appointment) {
                $client = $appointment->client;

                if ($client && $client->phone) {
                    // Real SMS notification via configured SMS service
                    \Illuminate\Support\Facades\$this->notification->route('sms', $client->phone)
                        ->notify(new \App\Notifications\AppointmentReminderNotification($appointment));
                }

                if ($client && $client->email) {
                    // Real email notification
                    \Illuminate\Support\Facades\Mail::to($client->email)
                        ->send(new \App\Mail\AppointmentReminderMail($appointment));
                }

                Log::channel('audit')->info('Reminder sent', [
                    'appointment_id' => $appointment->id,
                    'client_id' => $client?->id,
                    'methods' => ['sms' => (bool)$client?->phone, 'email' => (bool)$client?->email],
                    'correlation_id' => $this->correlationId,
                ]);
            }
        }
}
