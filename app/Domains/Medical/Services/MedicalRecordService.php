<?php declare(strict_types=1);

namespace App\Domains\Medical\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalRecordService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Создание новой записи (диагноз, рецепт, анализы).
         */
        public function createRecord(array $data, string $correlationId = null): MedicalRecord
        {
            $correlationId = $correlationId ?? (string)Str::uuid();
            $appointment = Appointment::findOrFail($data['appointment_id']);

            return DB::transaction(function () use ($data, $appointment, $correlationId) {

                $record = MedicalRecord::create([
                    'uuid' => (string)Str::uuid(),
                    'tenant_id' => $appointment->tenant_id,
                    'clinic_id' => $appointment->clinic_id,
                    'doctor_id' => $appointment->doctor_id,
                    'client_id' => $appointment->client_id,
                    'appointment_id' => $appointment->id,
                    'record_type' => $data['record_type'] ?? 'diagnosis',
                    'content' => $data['content'] ?? '',
                    'prescription_json' => $data['prescription'] ?? null,
                    'analysis_json' => $data['analysis'] ?? null,
                    'is_confidential' => $data['is_confidential'] ?? true,
                    'correlation_id' => $correlationId,
                    'metadata' => array_merge($data['metadata'] ?? [], [
                        'source' => 'doctor_interface',
                        'auth_user_id' => auth()->id()
                    ])
                ]);

                // Логируем доступ к созданной записи сразу (автор тоже логируется)
                $record->logAccess((int)auth()->id(), 'create');

                Log::channel('audit')->info('Medical record created', [
                    'record_id' => $record->id,
                    'record_type' => $record->record_type,
                    'appointment_id' => $appointment->id,
                    'is_confidential' => $record->is_confidential,
                    'correlation_id' => $correlationId
                ]);

                return $record;
            });
        }

        /**
         * Получение записи с обязательным логированием доступа (ФЗ-152 Audit).
         */
        public function getRecordForView(int $recordId, int $userId): MedicalRecord
        {
            $record = MedicalRecord::findOrFail($recordId);

            // КРИТИЧНО: Лог обращения к медкарте
            $record->logAccess($userId, 'view');

            Log::channel('audit')->info('Medical record accessed (view)', [
                'record_id' => $record->id,
                'user_id' => $userId,
                'correlation_id' => $record->correlation_id
            ]);

            return $record;
        }

        /**
         * Массовое получение истории пациента с логом доступа.
         */
        public function getPatientHistory(int $clientId, int $viewerId, array $filters = []): \Illuminate\Support\Collection
        {
            $records = MedicalRecord::where('client_id', $clientId)
                ->when($filters['clinic_id'] ?? null, fn($q, $id) => $q->where('clinic_id', $id))
                ->when($filters['type'] ?? null, fn($q, $type) => $q->where('record_type', $type))
                ->orderBy('created_at', 'desc')
                ->get();

            // Логируем массовый доступ
            if ($records->isNotEmpty()) {
                foreach ($records as $record) {
                    /** @var MedicalRecord $record */
                    $record->logAccess($viewerId, 'history_view');
                }
            }

            return $records;
        }

        /**
         * Маркировка записи как конфиденциальной или публичной.
         */
        public function updateConfidentiality(int $recordId, bool $isConfidential): void
        {
            $record = MedicalRecord::findOrFail($recordId);

            DB::transaction(function () use ($record, $isConfidential) {
                $record->update([
                    'is_confidential' => $isConfidential,
                    'correlation_id' => $record->correlation_id ?? (string)Str::uuid()
                ]);

                $record->logAccess((int)auth()->id(), 'confidentiality_update');
            });
        }
}
