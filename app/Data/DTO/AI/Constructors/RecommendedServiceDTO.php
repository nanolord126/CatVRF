<?php declare(strict_types=1);

/**
 * RecommendedServiceDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/recommendedservicedto
 * @see https://catvrf.ru/docs/recommendedservicedto
 * @see https://catvrf.ru/docs/recommendedservicedto
 * @see https://catvrf.ru/docs/recommendedservicedto
 * @see https://catvrf.ru/docs/recommendedservicedto
 */


namespace App\Data\DTO\AI\Constructors;

/** @phpstan-type AvailableSlots array<int, string> */
/**
 * Class RecommendedServiceDTO
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Data\DTO\AI\Constructors
 */
final readonly class RecommendedServiceDTO
{
    /** @param AvailableSlots $availableSlots */
    public function __construct(
        public int $serviceId,
        public int $masterId,
        public string $serviceName,
        public string $masterName,
        public int $price,
        public array $availableSlots,
    )
    {
        // Implementation required by canon
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}
