<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\LargeMammalGroomingSession;
use Carbon\CarbonImmutable;

interface LargeMammalGroomingSessionRepositoryInterface
{
    public function findById(int $id): ?LargeMammalGroomingSession;

    public function findByPetId(int $petId): array;

    public function findByGroomerId(int $groomerId, int $limit = 100): array;

    public function findByTenant(int $tenantId, int $limit = 100): array;

    public function findByMammalGroup(string $mammalGroup, int $tenantId, int $limit = 100): array;

    public function findHighAggressionSessions(int $tenantId, int $limit = 50): array;

    public function findWithSafetyIncidents(int $tenantId, int $limit = 50): array;

    public function findByDateRange(int $tenantId, CarbonImmutable $start, CarbonImmutable $end, int $limit = 100): array;

    public function findIncompleteByGroomer(int $groomerId): ?LargeMammalGroomingSession;

    public function save(LargeMammalGroomingSession $session): LargeMammalGroomingSession;

    public function delete(int $id): void;
}
