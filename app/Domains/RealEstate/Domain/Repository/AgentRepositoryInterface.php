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

use App\Domains\RealEstate\Domain\Entities\RealEstateAgent;
use App\Domains\RealEstate\Domain\ValueObjects\AgentId;
use Illuminate\Support\Collection;

interface AgentRepositoryInterface
{
    public function findById(AgentId $id): ?RealEstateAgent;

    public function findByIdAndTenant(AgentId $id, int $tenantId): ?RealEstateAgent;

    public function findByUserId(int $userId, int $tenantId): ?RealEstateAgent;

    /**
     * @return Collection<int, RealEstateAgent>
     */
    public function findActiveByTenant(int $tenantId): Collection;

    /**
     * @return Collection<int, RealEstateAgent>
     */
    public function findByTenantId(int $tenantId): Collection;

    public function save(RealEstateAgent $agent): void;

    public function delete(AgentId $id): void;
}
