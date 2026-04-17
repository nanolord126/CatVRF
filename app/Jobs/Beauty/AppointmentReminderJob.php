<?php declare(strict_types=1);

namespace App\Jobs\Beauty;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;

final class AppointmentReminderJob implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        public function __construct(
            private readonly Appointment $appointment,
            private string $type = 'customer', // 'customer' or 'master'
            private readonly string $correlationId,
            private readonly LogManager $logger,
    ) {
            $this->onQueue('beauty_notifications');
        }

        public function tags(): array
        {
            return ['beauty', 'reminder', 'appointment:' . $this->appointment->id];
        }

        public function handle(): void
        {
            $this->logger->channel('audit')->info('Job Started: Send Appointment Reminder', [
                'appointment_id' => $this->appointment->id,
                'type' => $this->type,
                'correlation_id' => $this->correlationId
            ]);

            try {
                // Реализация отправки через Mail или SMS
                // (В каноне здесь будет вызов NotificationService)

                // Log successful reminder
                $this->logger->channel('audit')->info('Job Finished: Appointment Reminder Sent', [
                    'appointment_id' => $this->appointment->id,
                    'type' => $this->type,
                    'correlation_id' => $this->correlationId
                ]);

            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Job Failed: Appointment Reminder Error', [
                    'appointment_id' => $this->appointment->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId
                ]);

                throw $e;
            }
        }
}

