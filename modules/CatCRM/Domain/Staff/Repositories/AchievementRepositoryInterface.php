<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Repositories;

use Modules\CatCRM\Domain\Staff\Achievement;
use Modules\CatCRM\Domain\Staff\ValueObjects\AchievementId;

/**
 * AchievementRepositoryInterface — Interface for Achievement repository
 */
interface AchievementRepositoryInterface
{
    public function findById(AchievementId $id): ?Achievement;

    public function findByEmployee(int $tenantId, int $employeeId): array;

    public function findByBadge(int $tenantId, int $badgeId): array;

    public function save(Achievement $achievement): bool;

    public function delete(AchievementId $id): bool;
}
