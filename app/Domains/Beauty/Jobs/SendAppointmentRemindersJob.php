<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final /**
 * SendAppointmentRemindersJob
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SendAppointmentRemindersJob implements ShouldQueue
{
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
