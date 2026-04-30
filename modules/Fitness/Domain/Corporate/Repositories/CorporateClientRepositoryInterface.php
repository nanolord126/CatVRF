<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Corporate\Repositories;

use Modules\Fitness\Domain\Corporate\Entities\CorporateClient;

interface CorporateClientRepositoryInterface
{
    public function save(CorporateClient $client): CorporateClient;

    public function findById(int $id): ?CorporateClient;

    public function findByTenantId(int $tenantId): array;

    public function findByInn(int $tenantId, string $inn): ?CorporateClient;

    public function findActiveByTenantId(int $tenantId): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
