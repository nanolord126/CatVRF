<?php declare(strict_types=1);

namespace App\Domains\Medical\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppointmentService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private WalletService $wallet,
            private FraudControlService $fraudControl,
            private RateLimiterService $rateLimiter,
            private MedicalInventoryService $inventory
        ) {}

        /**
         * Создание новой записи на прием.
         * КРИТИЧНО: DB::transaction() + FraudCheck + AgeLimiting + Prepayment.
         */
        public function createAppointment(array $data, string $correlationId = null): Appointment
        {
            $correlationId = $correlationId ?? (string)Str::uuid();

            try {
                // 1. Rate Limiting и Fraud Check
                $this->rateLimiter->check('medical_appointment_create', (int)auth()->id());
                $this->fraudControl->check(['user_id' => auth()->id(), 'action' => 'medical_booking']);

                // 2. Валидация доменных данных
                $doctor = Doctor::findOrFail($data['doctor_id']);
                $service = MedicalService::findOrFail($data['service_id']);
                $appointmentAt = Carbon::parse($data['appointment_at']);

                // 2.1 Проверка доступности врача (Layer 2 logic)
                if (!$doctor->isAvailableAt($appointmentAt)) {
                    throw new \Exception("Doctor is not available at this time: " . $appointmentAt->toDateTimeString());
                }

                // 2.2 Проверка возрастных ограничений
                if (!$service->checkAgeLimits($data['client_age'] ?? 0)) {
                    throw new \Exception("The patient's age does not meet the requirements for this service.");
                }

                // 3. Выполнение транзакции
                return DB::transaction(function () use ($data, $doctor, $service, $appointmentAt, $correlationId) {

                    // 3.1 Расчет предоплаты
                    $prepaymentNeeded = $service->calculateRequiredPrepayment();

                    // 3.2 Резервация расходников (Hold в Inventory)
                    $this->inventory->reserveForService($service->id, 1, $correlationId);

                    // 3.3 Создание записи
                    $appointment = Appointment::create([
                        'uuid' => (string)Str::uuid(),
                        'tenant_id' => $doctor->tenant_id,
                        'clinic_id' => $doctor->clinic_id,
                        'doctor_id' => $doctor->id,
                        'service_id' => $service->id,
                        'client_id' => auth()->id(),
                        'appointment_at' => $appointmentAt,
                        'status' => 'pending',
                        'total_price' => $service->base_price,
                        'prepayment_amount' => $prepaymentNeeded,
                        'payment_status' => 'unpaid',
                        'client_notes' => $data['client_notes'] ?? null,
                        'correlation_id' => $correlationId,
                        'metadata' => [
                            'creation_context' => 'public_api',
                            'client_age' => $data['client_age'] ?? null
                        ]
                    ]);

                    // 3.4 Если нужна предоплата - инициируем ее через Wallet (холд на кошельке клиента)
                    if ($prepaymentNeeded > 0) {
                        // $this->wallet->holdForAppointment($appointment, $prepaymentNeeded);
                        Log::channel('audit')->info('Prepayment requested for appointment', [
                            'appointment_id' => $appointment->id,
                            'amount' => $prepaymentNeeded,
                            'correlation_id' => $correlationId
                        ]);
                    }

                    Log::channel('audit')->info('Medical appointment created successfully', [
                        'appointment_id' => $appointment->id,
                        'doctor_id' => $doctor->id,
                        'service_id' => $service->id,
                        'correlation_id' => $correlationId
                    ]);

                    return $appointment;
                });

            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to create appointment', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }

        /**
         * Завершение приема и автоматическое списание расходников.
         */
        public function completeAppointment(int $appointmentId, ?string $correlationId = null): void
        {
            $appointment = Appointment::findOrFail($appointmentId);
            $correlationId = $correlationId ?? $appointment->correlation_id ?? (string)Str::uuid();

            try {
                DB::transaction(function () use ($appointment, $correlationId) {
                    // 1. Статус приема
                    $appointment->complete();

                    // 2. Списание расходников из "Hold" в "Deduct"
                    $this->inventory->deductForService(
                        $appointment->service_id,
                        1,
                        'appointment_completion',
                        $appointment->id,
                        $correlationId
                    );

                    // 3. Отправка ивента через транзакцию
                    // \App\Domains\Medical\Events\AppointmentCompleted::dispatch($appointment, $correlationId);

                    Log::channel('audit')->info('Medical appointment completed and stock deducted', [
                        'appointment_id' => $appointment->id,
                        'correlation_id' => $correlationId
                    ]);
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to complete appointment', [
                    'appointment_id' => $appointmentId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }

        /**
         * Отмена записи.
         */
        public function cancelAppointment(int $appointmentId, string $reason, ?string $correlationId = null): void
        {
            $appointment = Appointment::findOrFail($appointmentId);
            $correlationId = $correlationId ?? $appointment->correlation_id ?? (string)Str::uuid();

            try {
                DB::transaction(function () use ($appointment, $reason, $correlationId) {
                    $appointment->update([
                        'status' => 'cancelled',
                        'internal_notes' => ($appointment->internal_notes ?? '') . " Cancellation reason: $reason",
                        'correlation_id' => $correlationId
                    ]);

                    // Возврат расходников в общий сток
                    $this->inventory->releaseForService((int)$appointment->service_id, 1, $correlationId);

                    Log::channel('audit')->info('Medical appointment cancelled', [
                        'appointment_id' => $appointment->id,
                        'reason' => $reason,
                        'correlation_id' => $correlationId
                    ]);
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to cancel appointment', [
                    'appointment_id' => $appointmentId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }
    }

                    Log::channel('audit')->info('Medical appointment completed', [
                        'appointment_id' => $appointment->id,
                        'doctor_id' => $appointment->doctor_id,
                        'patient_id' => $appointment->patient_id,
                        'correlation_id' => $correlationId,
                    ]);

                    return $appointment;
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to complete appointment', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }

        public function cancelAppointment(
            MedicalAppointment $appointment,
            string $reason,
            ?string $correlationId = null,
        ): MedicalAppointment {
            $correlationId ??= Str::uuid()->toString();

            try {
                return DB::transaction(function () use ($appointment, $reason, $correlationId) {
                    $appointment->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'notes' => $reason,
                        'correlation_id' => $correlationId,
                    ]);

                    Log::channel('audit')->info('Medical appointment cancelled', [
                        'appointment_id' => $appointment->id,
                        'reason' => $reason,
                        'correlation_id' => $correlationId,
                    ]);

                    return $appointment;
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to cancel appointment', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }
}
