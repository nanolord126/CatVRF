<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\ExoticTrainingCourse;
use Modules\VetGrooming\Domain\Enums\CertificationLevel;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;

interface ExoticTrainingCourseRepositoryInterface
{
    public function findById(int $id): ?ExoticTrainingCourse;

    public function findByTenant(int $tenantId, int $limit = 100): array;

    public function findActiveByTenant(int $tenantId, int $limit = 100): array;

    public function findByCategory(ExoticCategory $category, int $tenantId, int $limit = 100): array;

    public function findByGroup(ExoticGroup $group, int $tenantId, int $limit = 100): array;

    public function findByLevel(CertificationLevel $level, int $tenantId, int $limit = 100): array;

    public function findMandatoryByTenant(int $tenantId, int $limit = 100): array;

    public function findMandatoryForCategory(ExoticCategory $category, int $tenantId, int $limit = 100): array;

    public function findByCategoryAndLevel(ExoticCategory $category, CertificationLevel $level, int $tenantId, int $limit = 100): array;

    public function save(ExoticTrainingCourse $course): ExoticTrainingCourse;

    public function delete(int $id): void;
}
