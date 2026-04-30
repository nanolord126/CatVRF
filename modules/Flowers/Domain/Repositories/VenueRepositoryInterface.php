<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Repositories;

use Modules\Flowers\Domain\Entities\Venue;
use Illuminate\Pagination\LengthAwarePaginator;

interface VenueRepositoryInterface
{
    public function findById(int $id): ?Venue;

    public function findBySlug(string $slug): ?Venue;

    public function save(Venue $venue): Venue;

    public function delete(int $id): void;

    public function getByTenant(int $tenantId, array $filters = []): LengthAwarePaginator;

    public function getByCity(string $city): array;

    public function getActive(): array;

    public function supportsDelivery(): array;
}
