<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Repositories;

use Modules\Fitness\Domain\Entities\Membership;

interface MembershipRepositoryInterface
{
    public function findById(int $id): ?Membership;

    public function findByClientId(int $clientId): array;

    public function findActiveByClientId(int $clientId): ?Membership;

    public function findByTenantId(int $tenantId): array;

    public function findExpiringSoon(int $tenantId, int $days = 7): array;

    public function findByType(int $tenantId, string $type): array;

    public function save(Membership $membership): Membership;

    public function delete(int $id): void;
}
