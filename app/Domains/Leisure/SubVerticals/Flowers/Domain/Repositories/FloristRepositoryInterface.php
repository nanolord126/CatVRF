<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Repositories;

use Modules\Flowers\Domain\Entities\Florist;
use Illuminate\Pagination\LengthAwarePaginator;

interface FloristRepositoryInterface
{
    public function findById(int $id): ?Florist;

    public function save(Florist $florist): Florist;

    public function delete(int $id): void;

    public function getByVenue(int $venueId, array $filters = []): LengthAwarePaginator;

    public function getAvailable(int $venueId): array;

    public function getTopRated(int $venueId): array;

    public function getExperienced(int $venueId): array;

    public function getByUserId(int $userId): ?Florist;
}
