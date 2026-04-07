<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;


use Psr\Log\LoggerInterface;
final class AppointmentReminderJob
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        private string $correlationId;

        public function __construct(
            readonly public Appointment $appointment,
            readonly public int $hoursBeforeAppointment = 24, private LoggerInterface $logger) {
            $this->correlationId = Uuid::uuid4()->toString();
        }

        public function handle(): void
        {
            try {
                $appointment = $this->appointment->refresh();

                // Проверить, что запись ещё актуальна и не отменена
                if ($appointment->status === 'cancelled' || $appointment->deleted_at) {
                    $this->logger->info('Appointment reminder skipped (cancelled)', [
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

                $this->logger->info('Appointment reminder sent', [
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
                $this->logger->error('AppointmentReminderJob failed', [
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
            $this->logger->error('AppointmentReminderJob permanently failed', [
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
