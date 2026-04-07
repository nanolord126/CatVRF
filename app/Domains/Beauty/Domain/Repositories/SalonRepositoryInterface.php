<?php

declare(strict_types=1);

/**
 * SalonRepositoryInterface — CatVRF 2026.
 *
 * Порт доступа к хранилищу салонов красоты.
 * Реализация в Infrastructure слое.
 *
 * @package CatVRF
 * @version 2026.1
 */


namespace App\Domains\Beauty\Domain\Repositories;

use App\Domains\Beauty\Domain\Entities\Salon;
use App\Domains\Beauty\Domain\ValueObjects\SalonId;
use App\Shared\Domain\ValueObjects\TenantId;
use Illuminate\Support\Collection;

interface SalonRepositoryInterface
{
    /**
     * @param SalonId $id
     * @return Salon|null
     */
    public function findById(SalonId $id): ?Salon;

    /**
     * @param TenantId $tenantId
     * @return Collection<int, Salon>
     */
    public function findByTenantId(TenantId $tenantId): Collection;

    /**
     * @param Salon $salon
     * @return void
     */
    public function save(Salon $salon): void;

    /**
     * @param SalonId $id
     * @return bool
     */
    public function delete(SalonId $id): bool;

    /**
     * @return SalonId
     */
    public function nextIdentity(): SalonId;

    /**
     * Найти салон по UUID.
     *
     * @param string $uuid
     * @return Salon|null
     */
    public function findByUuid(string $uuid): ?Salon;

    /**
     * Проверить существование салона по имени в рамках tenant.
     *
     * @param string $name
     * @param TenantId $tenantId
     * @return bool
     */
    public function existsByNameAndTenant(string $name, TenantId $tenantId): bool;
}
