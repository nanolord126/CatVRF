<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\ExoticSafetyProtocol;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;

interface ExoticSafetyProtocolRepositoryInterface
{
    public function findById(int $id): ?ExoticSafetyProtocol;

    public function findByTenant(int $tenantId, int $limit = 100): array;

    public function findActiveByTenant(int $tenantId, int $limit = 100): array;

    public function findByCategory(ExoticCategory $category, int $tenantId, int $limit = 100): array;

    public function findByGroup(ExoticGroup $group, int $tenantId, int $limit = 100): array;

    public function findBySpecies(string $species, int $tenantId, int $limit = 50): array;

    public function findByCategoryAndSpecies(ExoticCategory $category, string $species, int $tenantId): ?ExoticSafetyProtocol;

    public function findByGroupAndSpecies(ExoticGroup $group, string $species, int $tenantId): ?ExoticSafetyProtocol;

    public function save(ExoticSafetyProtocol $protocol): ExoticSafetyProtocol;

    public function delete(int $id): void;
}
