<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Repositories;

use Modules\Flowers\Domain\Entities\Client;
use Illuminate\Pagination\LengthAwarePaginator;

interface ClientRepositoryInterface
{
    public function findById(int $id): ?Client;

    public function findByPhone(string $phone): ?Client;

    public function findByEmail(string $email): ?Client;

    public function save(Client $client): Client;

    public function delete(int $id): void;

    public function getByTenant(int $tenantId, array $filters = []): LengthAwarePaginator;

    public function getVipClients(int $tenantId): array;

    public function getLoyalClients(int $tenantId): array;

    public function getClientsWithBirthdaySoon(int $tenantId, int $days = 7): array;
}
