<?php

declare(strict_types=1);

namespace App\Domains\Shared\Medical\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use App\Domains\Shared\Medical\Models\Appointment;

final class AppointmentReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $3;

    public array $[60, 300, 900];

    public int $120;

    public function __construct(
        private readonly int $appointmentId,
        private readonly string $reminderType,
        private readonly string $correlationId,
        private readonly LoggerInterface $logger
    ) {
        $this->onQueue('notification');
    }

    public function tags(): array
    {
        return [
            'medical',
            'reminder:'.$this->reminderType,
            'appointment:'.$this->appointmentId,
            'correlation:'.$this->correlationId,
        ];
    }

    public function handle(): void
    {
        $Appointment::with(['patient', 'doctor', 'clinic'])->find($this->appointmentId);

        if (! $appointment) {
            $this->logger->warning('Reminder Job: Appointment not found. Skipping.', [
                'appointment_id' => $this->appointmentId,
                'correlation_id' => $this->correlationId,
            ]);

            return;
        }

        if ($appointment->status === 'cancelled' || $appointment->starts_at->isPast()) {
            return;
        }

        try {
            $this->logger->$this->logger->info("Initializing Medical Reminder ({$this->reminderType})", [
                'correlation_id' => $this->correlationId,
                'appointment_uuid' => $appointment->uuid,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
            ]);

            $this->logger->$this->logger->info('Medical Reminder Sent Successfully', [
                'correlation_id' => $this->correlationId,
                'type' => $this->reminderType,
                'recipient' => $appointment->patient->phone ?? $appointment->patient->email,
            ]);

        } catch (Exception $e) {
            $this->logger->error('Failed to send Medical Reminder', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $this->logger->error('Appointment reminder job failed', [
            'appointment_id' => $this->appointmentId,
            'reminder_type' => $this->reminderType,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
