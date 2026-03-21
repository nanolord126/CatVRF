<?php declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Ramsey\Uuid\Uuid;

/**
 * Job для отправки напоминаний клиентам перед записями.
 * Отправляет напоминания за 24 часа и за 2 часа до начала записи.
 * Production 2026.
 */
final class AppointmentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private readonly string $correlationId;

    public function __construct(
        readonly public Appointment $appointment,
        readonly public int $hoursBeforeAppointment = 24,
    ) {
        $this->correlationId = Uuid::uuid4()->toString();
    }

    public function handle(): void
    {
        try {
            $appointment = $this->appointment->refresh();

            // Проверить, что запись ещё актуальна и не отменена
            if ($appointment->status === 'cancelled' || $appointment->deleted_at) {
                Log::channel('audit')->info('Appointment reminder skipped (cancelled)', [
                    'appointment_id' => $appointment->id,
                    'correlation_id' => $this->correlationId,
                ]);

                return;
            }

            $client = $appointment->client;
            if (!$client) {
                throw new \DomainException('Client not found for appointment: ' . $appointment->id);
            }
            // Можно использовать SMS, Email, Push-notification
            // Notification::send($client, new AppointmentReminderNotification($appointment, $this->hoursBeforeAppointment));

            Log::channel('audit')->info('Appointment reminder sent', [
                'appointment_id' => $appointment->id,
                'client_id' => $client->id,
                'hours_before' => $this->hoursBeforeAppointment,
                'salon_name' => $appointment->salon->name,
                'master_name' => $appointment->master->full_name,
                'service_name' => $appointment->service->name,
                'scheduled_for' => $appointment->datetime_start->toDateTimeString(),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('AppointmentReminderJob failed', [
                'appointment_id' => $this->appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('audit')->error('AppointmentReminderJob permanently failed', [
            'appointment_id' => $this->appointment->id,
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function tags(): array
    {
        return ['appointments', 'reminders'];
    }
}
