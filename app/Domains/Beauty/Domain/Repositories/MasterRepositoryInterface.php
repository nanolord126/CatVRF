<?php

declare(strict_types=1);

/**
 * MasterRepositoryInterface — CatVRF 2026.
 *
 * Порт доступа к хранилищу мастеров.
 * Реализация в Infrastructure слое.
 *
 * @package CatVRF
 * @version 2026.1
 */


namespace App\Domains\Beauty\Domain\Repositories;

use App\Domains\Beauty\Domain\Entities\Master;
use App\Domains\Beauty\Domain\ValueObjects\MasterId;
use App\Domains\Beauty\Domain\ValueObjects\SalonId;
use Illuminate\Support\Collection;

interface MasterRepositoryInterface
{
    /**
     * @param MasterId $id
     * @return Master|null
     */
    public function findById(MasterId $id): ?Master;

    /**
     * @param SalonId $salonId
     * @return Collection<int, Master>
     */
    public function findBySalonId(SalonId $salonId): Collection;

    /**
     * @param Master $master
     * @return void
     */
    public function save(Master $master): void;

    /**
     * @param MasterId $id
     * @return bool
     */
    public function delete(MasterId $id): bool;

    /**
     * @return MasterId
     */
    public function nextIdentity(): MasterId;

    /**
     * Найти всех активных мастеров салона.
     *
     * @param SalonId $salonId
     * @return Collection<int, Master>
     */
    public function findActiveBySalonId(SalonId $salonId): Collection;

    /**
     * Проверить существование мастера по ID.
     *
     * @param MasterId $id
     * @return bool
     */
    public function existsById(MasterId $id): bool;
}
