<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Repositories;

use Modules\CatCRM\Domain\Staff\Skill;
use Modules\CatCRM\Domain\Staff\ValueObjects\SkillId;

/**
 * SkillRepositoryInterface — Interface for Skill repository
 */
interface SkillRepositoryInterface
{
    public function findById(SkillId $id): ?Skill;

    public function findByTenant(int $tenantId): array;

    public function findByCategory(int $tenantId, string $category): array;

    public function findByEmployee(int $tenantId, int $employeeId): array;

    public function save(Skill $skill): bool;

    public function delete(SkillId $id): bool;
}
