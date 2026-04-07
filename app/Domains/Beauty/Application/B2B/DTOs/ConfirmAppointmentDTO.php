<?php

declare(strict_types=1);

/**
 * ConfirmAppointmentDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/confirmappointmentdto
 */


namespace App\Domains\Beauty\Application\B2B\DTOs;

/**
 * DTO для подтверждения записи администратором (PENDING → CONFIRMED).
 */
final readonly class ConfirmAppointmentDTO
{
    public function __construct(
        public int    $tenantId,
        public int    $confirmedByUserId,
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
            'tenant_id'           => $this->tenantId,
            'confirmed_by_user_id' => $this->confirmedByUserId,
            'appointment_uuid'    => $this->appointmentUuid,
            'correlation_id'      => $this->correlationId,
        ];
    }
}
