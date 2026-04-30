<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\Service;
use Illuminate\Support\Collection;

interface ServiceRepositoryInterface
{
    public function findById(int $id): ?Service;

    public function findByVenueId(int $venueId, bool $onlyActive = true): Collection;

    public function findBySlug(string $slug): ?Service;

    public function findByCategoryId(int $categoryId, bool $onlyActive = true): Collection;

    public function save(Service $service): Service;

    public function delete(int $id): bool;
}
