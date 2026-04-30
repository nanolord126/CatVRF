<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Interfaces;

use App\Domains\Advertising\Domain\Entities\Publisher;
use Illuminate\Database\Eloquent\Collection;

/**
 * Publisher Repository Interface
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
interface PublisherRepositoryInterface
{
    public function save(Publisher $publisher): Publisher;

    public function findById(int $id): ?Publisher;

    public function findByUuid(string $uuid): ?Publisher;

    /** @return Collection<int, Publisher> */
    public function findByTenant(int $tenantId): Collection;

    /** @return Collection<int, Publisher> */
    public function findActive(): Collection;

    public function findByApiKey(string $apiKey): ?Publisher;

    public function updateStatus(int $id, string $status): bool;

    public function updateApiKey(int $id, string $apiKey): bool;

    public function delete(int $id): bool;
}
