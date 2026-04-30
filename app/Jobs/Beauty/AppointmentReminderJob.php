<?php

declare(strict_types=1);

namespace App\Jobs\Beauty;

use Psr\Log\LoggerInterface;

use App\Models\Beauty\Appointment;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Carbon\CarbonImmutable;

final class AppointmentReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    private readonly string $correlationId;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly int $appointmentId,
        private readonly string $type,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,) {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('notification');
    }

    public function tags(): array
    {
        return ['beauty', 'reminder', 'appointment:' . $this->appointmentId];
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addMinutes(30);
    }

    public function handle(NotificationDispatcher $notificationDispatcher): void
    {
        $this->logger->channel('audit')->$this->logger->info('[AppointmentReminderJob] Started', [
            'appointment_id' => $this->appointmentId,
            'type' => $this->type,
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $appointment = Appointment::query()->find($this->appointmentId);

            if ($appointment === null) {
                $this->logger->channel('audit')->warning('[AppointmentReminderJob] Appointment not found', [
                    'appointment_id' => $this->appointmentId,
                    'correlation_id' => $this->correlationId,
                ]);

                return;
            }

            $recipient = $this->type === 'customer'
                ? $appointment->customer
                : $appointment->master;

            $notificationDispatcher->send(
                recipient: $recipient,
                type: 'beauty.appointment_reminder',
                data: [
                    'appointment_id' => $this->appointmentId,
                    'type' => $this->type,
                    'scheduled_at' => $appointment->scheduled_at?->toIso8601String(),
                ],
                correlationId: $this->correlationId,
            );

            $this->db->table('beauty_reminder_logs')->insert([
                'appointment_id' => $this->appointmentId,
                'type' => $this->type,
                'correlation_id' => $this->correlationId,
                'sent_at' => CarbonImmutable::now(),
            ]);

            $this->logger->channel('audit')->$this->logger->info('[AppointmentReminderJob] Completed', [
                'appointment_id' => $this->appointmentId,
                'type' => $this->type,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('[AppointmentReminderJob] Failed', [
                'appointment_id' => $this->appointmentId,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->channel('audit')->error('[AppointmentReminderJob] Failed permanently', [
            'appointment_id' => $this->appointmentId,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
