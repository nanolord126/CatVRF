<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\Client;
use Illuminate\Support\Collection;

interface ClientRepositoryInterface
{
    public function findById(int $id): ?Client;

    public function findByPhone(string $phone, ?int $venueId = null): ?Client;

    public function findByUserId(int $userId): ?Client;

    public function findByVenueId(int $venueId, array $filters = []): Collection;

    public function search(string $query, ?int $venueId = null): Collection;

    public function getVipClients(int $venueId): Collection;

    public function save(Client $client): Client;

    public function delete(int $id): bool;

    public function updateStats(int $id, array $stats): bool;
}
