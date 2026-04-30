<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Repositories;

use Modules\Flowers\Domain\Entities\Flower;
use Modules\Flowers\Domain\Enums\FreshnessStatus;
use Illuminate\Pagination\LengthAwarePaginator;

interface FlowerRepositoryInterface
{
    public function findById(int $id): ?Flower;

    public function findBySlug(string $slug): ?Flower;

    public function save(Flower $flower): Flower;

    public function delete(int $id): void;

    public function getByVenue(int $venueId, array $filters = []): LengthAwarePaginator;

    public function getByCategory(int $venueId, string $category): array;

    public function getByColor(int $venueId, string $color): array;

    public function getLowStock(int $venueId): array;

    public function getExpiringSoon(int $venueId, int $days = 3): array;

    public function getExpired(int $venueId): array;

    public function getByFreshnessStatus(int $venueId, FreshnessStatus $status): array;

    public function getAvailableForReservation(int $venueId, int $flowerId, int $quantity): bool;
}
