<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\GroomingSession;
use Modules\VetGrooming\Domain\Enums\GroomingStatus;
use Modules\VetGrooming\Domain\Enums\GroomingServiceType;
use Carbon\CarbonImmutable;

interface GroomingSessionRepositoryInterface
{
    public function findById(int $id): ?GroomingSession;

    public function findByUuid(string $uuid): ?GroomingSession;

    public function findByPetId(int $petId): array;

    public function findByPetIdWithPhotos(int $petId): array;

    public function findByGroomerId(int $groomerId): array;

    public function findByGroomerIdAndDate(int $groomerId, CarbonImmutable $date): array;

    public function findByClinicId(int $clinicId): array;

    public function findByStatus(GroomingStatus $status, int $limit = 100): array;

    public function findByStatusAndTenant(GroomingStatus $status, int $tenantId, int $limit = 100): array;

    public function findScheduledByDateRange(CarbonImmutable $start, CarbonImmutable $end, int $tenantId): array;

    public function findCompletedByPetId(int $petId): array;

    public function findLastByPetId(int $petId): ?GroomingSession;

    public function findByServiceType(GroomingServiceType $serviceType, int $limit = 100): array;

    public function findWithAggressiveBehavior(int $tenantId, int $limit = 50): array;

    public function save(GroomingSession $session): GroomingSession;

    public function delete(int $id): void;

    public function countByPetId(int $petId): int;

    public function countByGroomerIdAndDate(int $groomerId, CarbonImmutable $date): int;
}
