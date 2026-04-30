<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Repositories;

use Modules\CatCRM\Domain\Staff\Badge;
use Modules\CatCRM\Domain\Staff\ValueObjects\BadgeId;

/**
 * BadgeRepositoryInterface — Interface for Badge repository
 */
interface BadgeRepositoryInterface
{
    public function findById(BadgeId $id): ?Badge;

    public function findByTenant(int $tenantId): array;

    public function findByCategory(int $tenantId, string $category): array;

    public function save(Badge $badge): bool;

    public function delete(BadgeId $id): bool;
}
