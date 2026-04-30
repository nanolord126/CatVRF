<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\ExoticTrainingCompletion;
use Carbon\CarbonImmutable;

interface ExoticTrainingCompletionRepositoryInterface
{
    public function findById(int $id): ?ExoticTrainingCompletion;

    public function findByMasterId(int $masterId): array;

    public function findByMasterAndCourse(int $masterId, int $courseId): ?ExoticTrainingCompletion;

    public function findByCourseId(int $courseId, int $limit = 100): array;

    public function findByTenant(int $tenantId, int $limit = 100): array;

    public function findPassedByMasterId(int $masterId): array;

    public function findExpiringSoon(int $tenantId, int $days = 30, int $limit = 50): array;

    public function findExpired(int $tenantId, int $limit = 50): array;

    public function save(ExoticTrainingCompletion $completion): ExoticTrainingCompletion;

    public function delete(int $id): void;

    public function hasPassedCourse(int $masterId, int $courseId): bool;

    public function getMasterCompletionsForLevel(int $masterId, string $targetLevel): array;
}
