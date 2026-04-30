<?php

declare(strict_types=1);

namespace App\Domains\Common\Domain\Repositories;

use App\Domains\Common\Domain\Entities\Brand;
use Illuminate\Support\Collection;

interface BrandRepositoryInterface
{
    public function findById(int $id): ?Brand;

    public function findBySlug(string $slug): ?Brand;

    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): Collection;

    public function findActive(array $filters = [], int $limit = 100, int $offset = 0): Collection;

    public function findVerified(array $filters = [], int $limit = 100, int $offset = 0): Collection;

    public function search(string $query, array $filters = [], int $limit = 50, int $offset = 0): Collection;

    public function save(Brand $brand): Brand;

    public function delete(int $id): bool;

    public function count(array $filters = []): int;

    public function existsBySlug(string $slug): bool;
}
