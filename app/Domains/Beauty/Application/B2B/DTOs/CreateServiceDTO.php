<?php

declare(strict_types=1);

/**
 * CreateServiceDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createservicedto
 */


namespace App\Domains\Beauty\Application\B2B\DTOs;

/**
 * DTO для создания новой услуги в салоне (B2B).
 */
final readonly class CreateServiceDTO
{
    public function __construct(
        public int    $tenantId,
        public string $salonUuid,
        public string $name,
        public string $category,
        public int    $priceRubles,
        public int    $durationMinutes,
        public string $description,
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
            'category'         => $this->category,
            'price_rubles'     => $this->priceRubles,
            'duration_minutes' => $this->durationMinutes,
            'description'      => $this->description,
            'correlation_id'   => $this->correlationId,
        ];
    }
}
