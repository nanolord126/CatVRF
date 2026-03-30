<?php declare(strict_types=1);

namespace App\Domains\Medical\DTOs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppointmentData extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @param string $uuid Уникальный идентификатор записи (для идемпотентности)
         * @param int $tenantId Контекст клиники
         * @param int $clinicId ID клиники (для BusinessGroup)
         * @param int $doctorId ID врача
         * @param int $serviceId ID услуги
         * @param int $patientId ID пациента (пользователя)
         * @param Carbon $startsAt Время начала приема
         * @param int $durationMinutes Длительность в минутах
         * @param int $totalPrice Итоговая цена в копейках
         * @param int $prepaymentAmount Сумма обязательной предоплаты
         * @param string $correlationId ID для сквозного логирования
         * @param array $metadata Дополнительные данные (симптомы, примечания)
         * @param bool $isTelemedicine Флаг онлайн-консультации
         */
        public function __construct(
            public string $uuid,
            public int $tenantId,
            public int $clinicId,
            public int $doctorId,
            public int $serviceId,
            public int $patientId,
            public Carbon $startsAt,
            public int $durationMinutes,
            public int $totalPrice,
            public int $prepaymentAmount,
            public string $correlationId,
            public array $metadata = [],
            public bool $isTelemedicine = false,
        ) {
        }

        /**
         * Создание DTO из массива данных запроса (валидированного)
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
                clinicId: (int)$data['clinic_id'],
                doctorId: (int)$data['doctor_id'],
                serviceId: (int)$data['service_id'],
                patientId: (int)$data['patient_id'],
                startsAt: Carbon::parse($data['starts_at']),
                durationMinutes: (int)$data['duration_minutes'],
                totalPrice: (int)$data['total_price'],
                prepaymentAmount: (int)($data['prepayment_amount'] ?? 0),
                correlationId: $correlationId,
                metadata: $data['metadata'] ?? [],
                isTelemedicine: (bool)($data['is_telemedicine'] ?? false),
            );
        }

        /**
         * Создание DTO из существующей модели (например, для обновления)
         *
         * @param Appointment $appointment
         * @return self
         */
        public static function fromModel(Appointment $appointment): self
        {
            return new self(
                uuid: $appointment->uuid,
                tenantId: $appointment->tenant_id,
                clinicId: $appointment->clinic_id,
                doctorId: $appointment->doctor_id,
                serviceId: $appointment->service_id,
                patientId: $appointment->patient_id,
                startsAt: $appointment->starts_at,
                durationMinutes: $appointment->duration_minutes,
                totalPrice: $appointment->total_price,
                prepaymentAmount: $appointment->prepayment_amount,
                correlationId: $appointment->correlation_id ?? \Illuminate\Support\Str::uuid()->toString(),
                metadata: $appointment->metadata ?? [],
                isTelemedicine: $appointment->is_telemedicine,
            );
        }

        /**
         * Преобразование в массив для сохранения в БД
         *
         * @return array
         */
        public function toDatabaseArray(): array
        {
            return [
                'uuid' => $this->uuid,
                'tenant_id' => $this->tenantId,
                'clinic_id' => $this->clinicId,
                'doctor_id' => $this->doctorId,
                'service_id' => $this->serviceId,
                'patient_id' => $this->patientId,
                'starts_at' => $this->startsAt,
                'ends_at' => $this->startsAt->copy()->addMinutes($this->durationMinutes),
                'duration_minutes' => $this->durationMinutes,
                'total_price' => $this->totalPrice,
                'prepayment_amount' => $this->prepaymentAmount,
                'correlation_id' => $this->correlationId,
                'metadata' => $this->metadata,
                'is_telemedicine' => $this->isTelemedicine,
                'status' => 'pending', // Начальное состояние
            ];
        }
}
