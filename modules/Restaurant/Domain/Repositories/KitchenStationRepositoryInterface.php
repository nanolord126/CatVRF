<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Repositories;

use Modules\Restaurant\Domain\Entities\KitchenStation;
use Modules\Restaurant\Domain\Enums\KitchenStationType;
use Illuminate\Support\Collection;

interface KitchenStationRepositoryInterface
{
    public function findById(int $id): ?KitchenStation;

    public function findByType(KitchenStationType $type): Collection;

    public function findByTenantId(int $tenantId): Collection;

    public function save(KitchenStation $station): void;

    public function delete(int $id): void;

    public function getActiveStations(): Collection;
}
