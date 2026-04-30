<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\BreedCertification;
use Carbon\CarbonImmutable;

interface BreedCertificationRepositoryInterface
{
    public function findById(int $id): ?BreedCertification;

    public function findByUuid(string $uuid): ?BreedCertification;

    public function findByCertificateNumber(string $certificateNumber): ?BreedCertification;

    public function findByMasterId(int $masterId): array;

    public function findActiveByMasterId(int $masterId): array;

    public function findByMasterIdAndBreedGroup(int $masterId, string $breedGroup): array;

    public function findByMasterIdAndBreed(int $masterId, string $breed): array;

    public function findByMasterIdAndLevel(int $masterId, string $level): array;

    public function findByStatus(string $status, int $tenantId): array;

    public function findExpiringWithin(int $days, int $tenantId): array;

    public function findExpired(int $tenantId): array;

    public function findPendingVerification(int $tenantId): array;

    public function findByBreedGroup(string $breedGroup, string $requiredLevel, int $tenantId): array;

    public function findByBreed(string $breed, string $requiredLevel, int $tenantId): array;

    public function save(BreedCertification $certification): BreedCertification;

    public function delete(int $id): void;
}
