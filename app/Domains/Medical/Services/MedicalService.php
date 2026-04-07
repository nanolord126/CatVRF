<?php declare(strict_types=1);

namespace App\Domains\Medical\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class MedicalService
{
    public function __construct(private \App\Services\FraudControlService $fraud,
        private \App\Services\WalletService $wallet,
        private \App\Services\AuditService $audit,
        private \App\Domains\Medical\Services\AIMedicalTriageService $aiTriage,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Создание записи на прием (Appointment)
         */
        public function createAppointment(array $data): MedicalAppointment
        {
            $correlationId = $data['correlation_id'] ?? (string)Str::uuid();

            return $this->db->transaction(function () use ($data, $correlationId) {
                // 1. Fraud Check
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'medical_booking', amount: 0, correlationId: $correlationId ?? '');

                // 2. Атомарное создание записи
                $appointment = MedicalAppointment::create([
                    'uuid' => (string)Str::uuid(),
                    'tenant_id' => tenant()->id,
                    'clinic_id' => $data['clinic_id'],
                    'doctor_id' => $data['doctor_id'],
                    'service_id' => $data['service_id'],
                    'client_id' => $data['client_id'],
                    'starts_at' => $data['starts_at'],
                    'ends_at' => $data['ends_at'],
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'total_amount_kopecks' => $data['total_amount_kopecks'],
                    'correlation_id' => $correlationId,
                ]);

                // 3. Audit Log
                $this->logger->info('Medical appointment created', [
                    'appointment_uuid' => $appointment->uuid,
                    'correlation_id' => $correlationId,
                    'client_id' => $data['client_id'],
                ]);

                return $appointment;
            });
        }

        /**
         * Завершение приема и создание медицинской записи (Electronic Health Record)
         */
        public function completeAppointment(int $appointmentId, array $recordData): MedicalRecord
        {
            return $this->db->transaction(function () use ($appointmentId, $recordData) {
                $appointment = MedicalAppointment::findOrFail($appointmentId);

                // 1. Создание EHR (Medical Record)
                $record = MedicalRecord::create([
                    'uuid' => (string)Str::uuid(),
                    'tenant_id' => $appointment->tenant_id,
                    'patient_id' => $appointment->client_id,
                    'doctor_id' => $appointment->doctor_id,
                    'appointment_id' => $appointment->id,
                    'diagnosis_code' => $recordData['diagnosis_code'],
                    'complaints' => $recordData['complaints'],
                    'treatment_plan' => $recordData['treatment_plan'],
                    'clinical_data' => $recordData['clinical_data'] ?? [],
                    'correlation_id' => $appointment->correlation_id,
                ]);

                // 2. Обновление статуса приема
                $appointment->update(['status' => 'completed', 'completed_at' => now()]);

                // 3. Автоматическое списание расходников (КАНОН)
                event(new \App\Domains\Medical\Events\MedicalAppointmentCompleted($appointment, $record));

                $this->logger->info('Medical record finalized', [
                    'record_uuid' => $record->uuid,
                    'appointment_id' => $appointmentId,
                    'correlation_id' => $appointment->correlation_id,
                ]);

                return $record;
            });
        }

        /**
         * AI-триаж (предварительный диагноз)
         */
        public function performTriage(string $symptoms, int $userId): array
        {
            return $this->aiTriage->analyzeSymptoms($symptoms, $userId);
        }
}
