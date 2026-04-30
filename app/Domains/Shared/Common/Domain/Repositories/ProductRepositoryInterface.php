<?php

declare(strict_types=1);

namespace App\Domains\Common\Domain\Repositories;

use App\Domains\Common\Domain\Entities\Product;
use App\Domains\Common\Domain\ValueObjects\SKU;
use Illuminate\Support\Collection;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;

    public function findBySku(SKU $sku): ?Product;

    public function findAll(array $filters = [], int $limit = 50, int $offset = 0): Collection;

    public function findByCategory(int $categoryId, array $filters = [], int $limit = 50, int $offset = 0): Collection;

    public function findByBrand(int $brandId, array $filters = [], int $limit = 50, int $offset = 0): Collection;

    public function findActive(array $filters = [], int $limit = 50, int $offset = 0): Collection;

    public function findFeatured(array $filters = [], int $limit = 20, int $offset = 0): Collection;

    public function search(string $query, array $filters = [], int $limit = 50, int $offset = 0): Collection;

    public function save(Product $product): Product;

    public function delete(int $id): bool;

    public function count(array $filters = []): int;

    public function existsBySku(SKU $sku): bool;
}
