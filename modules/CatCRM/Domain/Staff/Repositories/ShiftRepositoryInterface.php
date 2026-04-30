<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Repositories;

use Modules\CatCRM\Domain\Staff\Shift;
use Modules\CatCRM\Domain\Staff\ValueObjects\ShiftId;
use Carbon\CarbonImmutable;

/**
 * ShiftRepositoryInterface — Interface for Shift repository
 */
interface ShiftRepositoryInterface
{
    public function findById(ShiftId $id): ?Shift;

    public function findByEmployee(int $tenantId, int $employeeId): array;

    public function findByDateRange(int $tenantId, CarbonImmutable $start, CarbonImmutable $end): array;

    public function findActiveByEmployee(int $tenantId, int $employeeId): ?Shift;

    public function save(Shift $shift): bool;

    public function delete(ShiftId $id): bool;
}
