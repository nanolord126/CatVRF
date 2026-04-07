<?php

declare(strict_types=1);

/**
 * ConsumableRepositoryInterface — CatVRF 2026.
 *
 * Порт доступа к хранилищу расходных материалов.
 * Реализация в Infrastructure слое.
 *
 * @package CatVRF
 * @version 2026.1
 */


namespace App\Domains\Beauty\Domain\Repositories;

use App\Domains\Beauty\Domain\Entities\Consumable;
use App\Domains\Beauty\Domain\ValueObjects\ServiceId;
use Illuminate\Support\Collection;

interface ConsumableRepositoryInterface
{
    /**
     * Найти расходник по первичному ключу.
     */
    public function findById(int $id): ?Consumable;

    /**
     * Все расходники, связанные с конкретной услугой.
     *
     * @return Collection<int, Consumable>
     */
    public function findByServiceId(ServiceId $serviceId): Collection;

    /**
     * Все расходники tenant, запас которых ниже порогового значения.
     *
     * @return Collection<int, Consumable>
     */
    public function findBelowThreshold(int $tenantId): Collection;

    /**
     * Сохранить (insert или update) расходник.
     */
    public function save(Consumable $consumable): void;

    /**
     * Удалить расходник по ID.
     */
    public function delete(int $id): bool;

    /**
     * Сгенерировать следующий идентификатор расходника.
     *
     * @return int
     */
    public function nextIdentity(): int;

    /**
     * Получить все расходники tenant.
     *
     * @param int $tenantId
     * @return Collection<int, Consumable>
     */
    public function findAllByTenantId(int $tenantId): Collection;
}
