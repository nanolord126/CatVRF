<?php

declare(strict_types=1);

/**
 * BookAppointmentB2CDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/bookappointmentb2cdto
 */


namespace App\Domains\Beauty\Application\B2C\DTOs;

/**
 * DTO для бронирования записи клиентом (B2C).
 */
final readonly class BookAppointmentB2CDTO
{
    public function __construct(
        public int    $tenantId,
        public int    $clientId,
        public string $salonUuid,
        public string $masterUuid,
        public string $serviceUuid,
        public string $startAt,
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
            'tenant_id'      => $this->tenantId,
            'client_id'      => $this->clientId,
            'salon_uuid'     => $this->salonUuid,
            'master_uuid'    => $this->masterUuid,
            'service_uuid'   => $this->serviceUuid,
            'start_at'       => $this->startAt,
            'correlation_id' => $this->correlationId,
        ];
    }
}
