<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\TrainingCompletion;
use Carbon\CarbonImmutable;

interface TrainingCompletionRepositoryInterface
{
    public function findById(int $id): ?TrainingCompletion;

    public function findByUuid(string $uuid): ?TrainingCompletion;

    public function findByMasterId(int $masterId): array;

    public function findByMasterIdAndCourseId(int $masterId, int $courseId): ?TrainingCompletion;

    public function findByDevelopmentPlanId(int $developmentPlanId): array;

    public function findByCourseId(int $courseId): array;

    public function findByVerificationStatus(string $status, int $tenantId): array;

    public function findPendingVerification(int $tenantId): array;

    public function findCertificatesExpiringWithin(int $days, int $tenantId): array;

    public function findExpiredCertificates(int $tenantId): array;

    public function findByMasterIdAndDateRange(int $masterId, CarbonImmutable $start, CarbonImmutable $end): array;

    public function countByMasterId(int $masterId): int;

    public function save(TrainingCompletion $completion): TrainingCompletion;

    public function delete(int $id): void;
}
