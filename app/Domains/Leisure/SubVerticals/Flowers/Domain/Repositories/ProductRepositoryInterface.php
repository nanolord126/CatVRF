<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Repositories;

use Modules\Flowers\Domain\Entities\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;

    public function findBySlug(string $slug): ?Product;

    public function save(Product $product): Product;

    public function delete(int $id): void;

    public function getByVenue(int $venueId, array $filters = []): LengthAwarePaginator;

    public function getFeatured(int $venueId): array;

    public function getByCategory(int $venueId, string $category): array;

    public function getBySize(int $venueId, string $size): array;

    public function getOnDiscount(int $venueId): array;

    public function getActive(int $venueId): array;
}
