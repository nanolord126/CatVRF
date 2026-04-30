<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\SmallMammalGroomingSession;
use Carbon\CarbonImmutable;

interface SmallMammalGroomingSessionRepositoryInterface
{
    public function findById(int $id): ?SmallMammalGroomingSession;

    public function findByPetId(int $petId): array;

    public function findByGroomerId(int $groomerId, int $limit = 100): array;

    public function findByTenant(int $tenantId, int $limit = 100): array;

    public function findByMammalGroup(string $mammalGroup, int $tenantId, int $limit = 100): array;

    public function findHighStressSessions(int $tenantId, int $limit = 50): array;

    public function findWithSedation(int $tenantId, int $limit = 50): array;

    public function findByDateRange(int $tenantId, CarbonImmutable $start, CarbonImmutable $end, int $limit = 100): array;

    public function findIncompleteByGroomer(int $groomerId): ?SmallMammalGroomingSession;

    public function save(SmallMammalGroomingSession $session): SmallMammalGroomingSession;

    public function delete(int $id): void;
}
