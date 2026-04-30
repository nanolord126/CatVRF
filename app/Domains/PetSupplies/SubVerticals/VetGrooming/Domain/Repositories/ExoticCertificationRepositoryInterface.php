<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\ExoticCertification;
use Modules\VetGrooming\Domain\Enums\CertificationLevel;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;
use Carbon\CarbonImmutable;

interface ExoticCertificationRepositoryInterface
{
    public function findById(int $id): ?ExoticCertification;

    public function findByMasterId(int $masterId): array;

    public function findByMasterAndCategory(int $masterId, ExoticCategory $category): ?ExoticCertification;

    public function findByMasterAndGroup(int $masterId, ExoticGroup $group): ?ExoticCertification;

    public function findValidByMasterAndCategory(int $masterId, ExoticCategory $category): ?ExoticCertification;

    public function findValidByMasterAndGroup(int $masterId, ExoticGroup $group): ?ExoticCertification;

    public function findByTenant(int $tenantId, int $limit = 100): array;

    public function findByLevel(CertificationLevel $level, int $tenantId, int $limit = 100): array;

    public function findByCategory(ExoticCategory $category, int $tenantId, int $limit = 100): array;

    public function findExpiringSoon(int $tenantId, int $days = 30, int $limit = 50): array;

    public function findExpired(int $tenantId, int $limit = 50): array;

    public function findRequiringRenewal(int $tenantId, int $limit = 50): array;

    public function save(ExoticCertification $certification): ExoticCertification;

    public function delete(int $id): void;

    public function exists(int $masterId, ExoticCategory $category, ExoticGroup $group): bool;
}
