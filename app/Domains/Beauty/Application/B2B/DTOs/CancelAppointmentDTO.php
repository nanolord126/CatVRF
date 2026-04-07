<?php

declare(strict_types=1);

/**
 * CancelAppointmentDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/cancelappointmentdto
 */


namespace App\Domains\Beauty\Application\B2B\DTOs;

/**
 * DTO для отмены записи администратором (любой статус → CANCELLED).
 */
final readonly class CancelAppointmentDTO
{
    public function __construct(
        public int    $tenantId,
        public int    $cancelledByUserId,
        public string $appointmentUuid,
        public string $reason,
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
            'cancelled_by_user_id' => $this->cancelledByUserId,
            'appointment_uuid'     => $this->appointmentUuid,
            'reason'               => $this->reason,
            'correlation_id'       => $this->correlationId,
        ];
    }
}
