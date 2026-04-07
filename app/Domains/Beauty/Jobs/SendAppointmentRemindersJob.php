<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;


use Carbon\Carbon;
use App\Domains\Beauty\Models\Appointment;
use App\Notifications\AppointmentReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * SendAppointmentRemindersJob — отправляет напоминания клиентам
 * за 24 часа и за 2 часа до записи.
 *
 * Запускается каждые 30 минут через Scheduler.
 */
final class SendAppointmentRemindersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    private string $correlationId;

    public function __construct(string $correlationId = '')
    {
        $this->correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();
    }

    public function handle(LoggerInterface $logger): void
    {
        $appointments = Appointment::query()
            ->where('status', 'confirmed')
            ->whereBetween('datetime_start', [Carbon::now(), Carbon::now()->addHours(24)])
            ->with(['client', 'master', 'service', 'salon'])
            ->get();

        foreach ($appointments as $appointment) {
            $client = $appointment->client;

            if ($client === null) {
                continue;
            }

            $client->notify(new AppointmentReminderNotification($appointment));

            $logger->info('Appointment reminder sent.', [
                'appointment_id' => $appointment->id,
                'client_id'      => $client->id,
                'has_email'      => $client->email !== null,
                'has_phone'      => $client->phone !== null,
                'correlation_id' => $this->correlationId,
            ]);
        }

        $logger->info('SendAppointmentRemindersJob completed.', [
            'sent_count'     => $appointments->count(),
            'correlation_id' => $this->correlationId,
        ]);
    }

    /** @return array<int, string> */
    public function tags(): array
    {
        return ['beauty', 'job:appointment-reminders'];
    }
}
