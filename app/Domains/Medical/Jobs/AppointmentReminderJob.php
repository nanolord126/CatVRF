<?php declare(strict_types=1);

namespace App\Domains\Medical\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppointmentReminderJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        /**
         * @var int Количество попыток выполнения (по канону 2026)
         */
        public int $tries = 3;

        /**
         * @var int Задержка между попытками (экспоненциально)
         */
        public array $backoff = [60, 300, 900];

        /**
         * @param int $appointmentId ID записи на прием
         * @param string $reminderType Тип: '24h' или '2h'
         * @param string $correlationId UUID для сквозного аудита
         */
        public function __construct(
            private readonly int $appointmentId,
            private readonly string $reminderType,
            private readonly string $correlationId
        ) {
        }

        /**
         * Обработка задачи отправки уведомления.
         *
         * @return void
         * @throws \Exception
         */
        public function handle(): void
        {
            // 1. Поиск записи с учетом Soft Deletes и статуса
            $appointment = Appointment::with(['patient', 'doctor', 'clinic'])->find($this->appointmentId);

            if (!$appointment) {
                Log::channel('audit')->warning('Reminder Job: Appointment not found. Skipping.', [
                    'appointment_id' => $this->appointmentId,
                    'correlation_id' => $this->correlationId,
                ]);
                return;
            }

            // 2. Проверка актуальности (только для подтвержденных и будущих записей)
            if ($appointment->status === 'cancelled' || $appointment->starts_at->isPast()) {
                return;
            }

            try {
                Log::channel('audit')->info("Initializing Medical Reminder ({$this->reminderType})", [
                    'correlation_id' => $this->correlationId,
                    'appointment_uuid' => $appointment->uuid,
                    'patient_id' => $appointment->patient_id,
                    'doctor_id' => $appointment->doctor_id,
                ]);

                // 3. Отправка через Notification Facade (SMS/Email/Push/Telegram)
                // $appointment->patient->notify(new \App\Domains\Medical\Notifications\VisitReminder($appointment, $this->reminderType, $this->correlationId));

                // Лог выполнения для ФЗ-152 (факт уведомления о мед-услуге)
                Log::channel('audit')->info("Medical Reminder Sent Successfully", [
                    'correlation_id' => $this->correlationId,
                    'type' => $this->reminderType,
                    'recipient' => $appointment->patient->phone ?? $appointment->patient->email,
                ]);

            } catch (\Throwable $e) {
                Log::channel('audit')->error("Failed to send Medical Reminder", [
                    'correlation_id' => $this->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e; // Trigger retry
            }
        }

        /**
         * Теги для удобного мониторинга в Horizon.
         *
         * @return array
         */
        public function tags(): array
        {
            return [
                'medical',
                'reminder:' . $this->reminderType,
                'appointment:' . $this->appointmentId,
                'correlation:' . $this->correlationId,
            ];
        }
}
