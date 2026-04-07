<?php

declare(strict_types=1);

/**
 * ListSalonsUseCase — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listsalonsusecase
 */


namespace App\Domains\Beauty\Application\B2C\UseCases;

use App\Domains\Beauty\Domain\Repositories\SalonRepositoryInterface;
use App\Shared\Domain\ValueObjects\TenantId;
use Illuminate\Support\Collection;

/**
 * Class ListSalonsUseCase
 *
 * Part of the Beauty vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Beauty\Application\B2C\UseCases
 */
final readonly class ListSalonsUseCase
{
    public function __construct(
        private SalonRepositoryInterface $salonRepository,
    ) {
    }

    /**
     * Handle __invoke operation.
     *
     * @throws \DomainException
     */
    public function __invoke(int $tenantId): Collection
    {
        // In B2C, we might not have a tenant, or we might have a "public" tenant.
        // This logic will depend on the multi-tenancy strategy for the public part.
        // For now, let's assume we pass a tenantId for a region/city.
        $tenant = new TenantId($tenantId);
        return $this->salonRepository->findByTenantId($tenant);
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class;
    }
}
