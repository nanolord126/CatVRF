<?php

declare(strict_types=1);

namespace App\Domains\Medical\DTOs;

/**
 * РЕЖИМ ЛЮТЫЙ 2026: MEDICAL RECORD DATA DTO
 * 
 * Объект для передачи данных медицинских карт/записей (ФЗ-152).
 * Обеспечивает строгий аудит всех вносимых правок.
 * 
 * @package App\Domains\Medical\DTOs
 */
final readonly class MedicalRecordData
{
    /**
     * @param string $uuid ID медицинской записи
     * @param int $tenantId Клиника
     * @param int $patientId ID пациента
     * @param int $doctorId ID врача-автора
     * @param int $appointmentId Связанная запись на прием
     * @param string $diagnosis Основной диагноз (МКБ-10)
     * @param string $complaints Жалобы
     * @param string $examination Осмотр/Обследование
     * @param string $treatment Рекомендации и лечение
     * @param string $correlationId UUID для аудита
     * @param array $files Ссылки на прикрепленные документы/анализы
     * @param array $history Изменения в записи (для ФЗ-152)
     */
    public function __construct(
        public string $uuid,
        public int $tenantId,
        public int $patientId,
        public int $doctorId,
        public int $appointmentId,
        public string $diagnosis,
        public string $complaints,
        public string $examination,
        public string $treatment,
        public string $correlationId,
        public array $files = [],
        public array $history = [],
    ) {
    }

    /**
     * Создание DTO из данных запроса
     * 
     * @param array $data
     * @param string $correlationId
     * @return self
     */
    public static function fromArray(array $data, string $correlationId): self
    {
        return new self(
            uuid: $data['uuid'] ?? \Illuminate\Support\Str::uuid()->toString(),
            tenantId: (int)$data['tenant_id'],
            patientId: (int)$data['patient_id'],
            doctorId: (int)$data['doctor_id'],
            appointmentId: (int)$data['appointment_id'],
            diagnosis: $data['diagnosis'] ?? 'Z00.0', // Общее обследование
            complaints: $data['complaints'] ?? '',
            examination: $data['examination'] ?? '',
            treatment: $data['treatment'] ?? '',
            correlationId: $correlationId,
            files: $data['files'] ?? [],
            history: $data['history'] ?? [],
        );
    }

    /**
     * Преобразование в массив для БД
     * 
     * @return array
     */
    public function toDBRow(): array
    {
        return [
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenantId,
            'patient_id' => $this->patientId,
            'doctor_id' => $this->doctorId,
            'appointment_id' => $this->appointmentId,
            'diagnosis' => $this->diagnosis,
            'complaints' => $this->complaints,
            'examination' => $this->examination,
            'treatment' => $this->treatment,
            'correlation_id' => $this->correlationId,
            'files' => json_encode($this->files),
            'access_log_json' => json_encode([
                [
                    'user_id' => auth()->id(),
                    'action' => 'created',
                    'timestamp' => now()->toIso8601String(),
                    'correlation_id' => $this->correlationId,
                ]
            ]),
        ];
    }

    /**
     * Валидация полноты данных для медицинской записи
     * 
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        if (empty($this->diagnosis)) {
            throw new \InvalidArgumentException('Diagnosis is mandatory for medical records.');
        }

        if (empty($this->complaints) && empty($this->treatment)) {
            throw new \InvalidArgumentException('Medical record must contain complaints or treatment recommendations.');
        }
    }
}
