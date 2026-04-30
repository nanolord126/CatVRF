<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\Master;
use Illuminate\Support\Collection;

interface MasterRepositoryInterface
{
    public function findById(int $id): ?Master;

    public function findByVenueId(int $venueId, bool $onlyActive = true): Collection;

    public function findByUserId(int $userId): ?Master;

    public function findBySlug(string $slug): ?Master;

    public function findWithSchedule(int $id, string $dayOfWeek): ?Master;

    public function save(Master $master): Master;

    public function delete(int $id): bool;

    public function updateRating(int $id, float $rating, int $totalReviews): bool;
}
