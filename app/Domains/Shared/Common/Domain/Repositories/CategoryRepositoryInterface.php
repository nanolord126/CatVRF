<?php

declare(strict_types=1);

namespace App\Domains\Common\Domain\Repositories;

use App\Domains\Common\Domain\Entities\Category;
use Illuminate\Support\Collection;

interface CategoryRepositoryInterface
{
    public function findById(int $id): ?Category;

    public function findBySlug(string $slug): ?Category;

    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): Collection;

    public function findRootCategories(array $filters = []): Collection;

    public function findByParent(int $parentId, array $filters = []): Collection;

    public function findActive(array $filters = [], int $limit = 100, int $offset = 0): Collection;

    public function getTree(int? $parentId = null): Collection;

    public function save(Category $category): Category;

    public function delete(int $id): bool;

    public function count(array $filters = []): int;

    public function existsBySlug(string $slug): bool;

    public function hasChildren(int $categoryId): bool;
}
