<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Repositories;

use Modules\CatCRM\Domain\Staff\Leave;
use Modules\CatCRM\Domain\Staff\ValueObjects\LeaveId;

/**
 * LeaveRepositoryInterface — Interface for Leave repository
 */
interface LeaveRepositoryInterface
{
    public function findById(LeaveId $id): ?Leave;

    public function findByEmployee(int $tenantId, int $employeeId): array;

    public function findPendingByEmployee(int $tenantId, int $employeeId): array;

    public function findActiveByEmployee(int $tenantId, int $employeeId): ?Leave;

    public function save(Leave $leave): bool;

    public function delete(LeaveId $id): bool;
}
