<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\SkillMatrix;

interface SkillMatrixRepositoryInterface
{
    public function findById(int $id): ?SkillMatrix;

    public function findByUuid(string $uuid): ?SkillMatrix;

    public function findByMasterId(int $masterId): array;

    public function findByMasterIdAndSkill(int $masterId, string $skill): ?SkillMatrix;

    public function findByMasterIdAndCategory(int $masterId, string $category): array;

    public function findByProficiencyLevel(string $level, int $tenantId): array;

    public function findByMasterIdAndProficiencyLevel(int $masterId, string $level): array;

    public function save(SkillMatrix $skillMatrix): SkillMatrix;

    public function delete(int $id): void;

    public function deleteByMasterId(int $masterId): void;
}
