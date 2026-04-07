<?php

declare(strict_types=1);

/**
 * CompleteAppointmentDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/completeappointmentdto
 */


namespace App\Domains\Beauty\Application\B2B\DTOs;

/**
 * DTO для завершения услуги (CONFIRMED → COMPLETED).
 * После завершения автоматически диспатчится AppointmentCompleted event
 * → DeductAppointmentConsumablesListener.
 */
final readonly class CompleteAppointmentDTO
{
    public function __construct(
        public int    $tenantId,
        public int    $completedByUserId,
        public string $appointmentUuid,
        public string $correlationId,
    ) {}

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . $this->correlationId;
    }
/**
     * Преобразование в массив для audit-лога и сериализации.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id'            => $this->tenantId,
            'completed_by_user_id' => $this->completedByUserId,
            'appointment_uuid'     => $this->appointmentUuid,
            'correlation_id'       => $this->correlationId,
        ];
    }
}
