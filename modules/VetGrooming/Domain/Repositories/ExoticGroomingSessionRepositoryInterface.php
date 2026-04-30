<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\ExoticGroomingSession;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Carbon\CarbonImmutable;

interface ExoticGroomingSessionRepositoryInterface
{
    public function findById(int $id): ?ExoticGroomingSession;

    public function findByPetId(int $petId): array;

    public function findByGroomerId(int $groomerId, int $limit = 100): array;

    public function findByTenant(int $tenantId, int $limit = 100): array;

    public function findByCategory(ExoticCategory $category, int $tenantId, int $limit = 100): array;

    public function findBySpeciesGroup(string $speciesGroup, int $tenantId, int $limit = 100): array;

    public function findHighStressSessions(int $tenantId, int $limit = 50): array;

    public function findWithSedation(int $tenantId, int $limit = 50): array;

    public function findByDateRange(int $tenantId, CarbonImmutable $start, CarbonImmutable $end, int $limit = 100): array;

    public function findIncompleteByGroomer(int $groomerId): ?ExoticGroomingSession;

    public function save(ExoticGroomingSession $session): ExoticGroomingSession;

    public function delete(int $id): void;
}
