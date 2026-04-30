<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Repositories;

use Modules\Fitness\Domain\Entities\Client;

interface ClientRepositoryInterface
{
    public function findById(int $id): ?Client;

    public function findByUserId(int $userId): ?Client;

    public function findByTenantId(int $tenantId): array;

    public function findByPhone(int $tenantId, string $phone): ?Client;

    public function findByEmail(int $tenantId, string $email): ?Client;

    public function searchByName(int $tenantId, string $name): array;

    public function findTopByVisits(int $tenantId, int $limit = 10): array;

    public function save(Client $client): Client;

    public function delete(int $id): void;
}
