<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\TrainingCourse;

interface TrainingCourseRepositoryInterface
{
    public function findById(int $id): ?TrainingCourse;

    public function findByUuid(string $uuid): ?TrainingCourse;

    public function findByTenantId(int $tenantId): array;

    public function findActiveByTenantId(int $tenantId): array;

    public function findByProfessionType(string $professionType, int $tenantId): array;

    public function findByProfessionTypeAndCategory(string $professionType, string $category, int $tenantId): array;

    public function findMandatoryByProfessionType(string $professionType, int $tenantId): array;

    public function findMandatoryForSpecialization(string $specialization, int $tenantId): array;

    public function findMandatoryForBreed(string $breed, int $tenantId): array;

    public function findByRequiredLevel(string $level, int $tenantId): array;

    public function save(TrainingCourse $course): TrainingCourse;

    public function delete(int $id): void;
}
