<?php

declare(strict_types=1);

/**
 * ServiceRepositoryInterface — CatVRF 2026.
 *
 * Порт доступа к хранилищу услуг салонов.
 * Реализация в Infrastructure слое.
 *
 * @package CatVRF
 * @version 2026.1
 */


namespace App\Domains\Beauty\Domain\Repositories;

use App\Domains\Beauty\Domain\Entities\Service;
use App\Domains\Beauty\Domain\ValueObjects\ServiceId;
use App\Shared\Domain\ValueObjects\TenantId;
use Illuminate\Support\Collection;

interface ServiceRepositoryInterface
{
    /**
     * @param ServiceId $id
     * @return Service|null
     */
    public function findById(ServiceId $id): ?Service;

    /**
     * @param TenantId $tenantId
     * @return Collection<int, Service>
     */
    public function findByTenantId(TenantId $tenantId): Collection;

    /**
     * @param Service $service
     * @return void
     */
    public function save(Service $service): void;

    /**
     * @param ServiceId $id
     * @return bool
     */
    public function delete(ServiceId $id): bool;

    /**
     * @return ServiceId
     */
    public function nextIdentity(): ServiceId;

    /**
     * Найти все активные услуги tenant.
     *
     * @param TenantId $tenantId
     * @return Collection<int, Service>
     */
    public function findActiveByTenantId(TenantId $tenantId): Collection;

    /**
     * Проверить существование услуги по ID.
     *
     * @param ServiceId $id
     * @return bool
     */
    public function existsById(ServiceId $id): bool;
}
