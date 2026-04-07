<?php

declare(strict_types=1);

/**
 * CreateMasterDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createmasterdto
 */


namespace App\Domains\Beauty\Application\B2B\DTOs;

/**
 * DTO для регистрации нового мастера в салоне (B2B).
 */
final readonly class CreateMasterDTO
{
    public function __construct(
        public int    $tenantId,
        public string $salonUuid,
        public string $name,
        public string $specialization,
        public int    $experienceYears,
        public array  $workDays,
        public string $workStart,
        public string $workEnd,
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
            'tenant_id'        => $this->tenantId,
            'salon_uuid'       => $this->salonUuid,
            'name'             => $this->name,
            'specialization'   => $this->specialization,
            'experience_years' => $this->experienceYears,
            'work_days'        => $this->workDays,
            'work_start'       => $this->workStart,
            'work_end'         => $this->workEnd,
            'correlation_id'   => $this->correlationId,
        ];
    }
}
