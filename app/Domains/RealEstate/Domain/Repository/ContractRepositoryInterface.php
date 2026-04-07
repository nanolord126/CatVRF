<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\RealEstate\Domain\Repository;

use App\Domains\RealEstate\Domain\Entities\Contract;
use App\Domains\RealEstate\Domain\ValueObjects\ContractId;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use Illuminate\Support\Collection;

interface ContractRepositoryInterface
{
    public function findById(ContractId $id): ?Contract;

    public function findByIdAndTenant(ContractId $id, int $tenantId): ?Contract;

    /**
     * @return Collection<int, Contract>
     */
    public function findByPropertyId(PropertyId $propertyId): Collection;

    /**
     * @return Collection<int, Contract>
     */
    public function findByTenantId(int $tenantId): Collection;

    /**
     * @return Collection<int, Contract>
     */
    public function findSignedByTenant(int $tenantId): Collection;

    public function save(Contract $contract): void;
}
