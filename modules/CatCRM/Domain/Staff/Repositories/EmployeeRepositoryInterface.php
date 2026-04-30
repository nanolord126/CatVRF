<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Repositories;

use Modules\CatCRM\Domain\Staff\Employee;
use Modules\CatCRM\Domain\Staff\ValueObjects\EmployeeId;

/**
 * EmployeeRepositoryInterface — Interface for Employee repository
 * 
 * Following Clean Architecture and DDD principles
 */
interface EmployeeRepositoryInterface
{
    public function findById(EmployeeId $id): ?Employee;

    public function findByUserId(int $userId, int $tenantId): ?Employee;

    public function findByTenant(int $tenantId): array;

    public function findByDepartment(int $tenantId, string $department): array;

    public function findByManager(int $tenantId, int $managerId): array;

    public function save(Employee $employee): bool;

    public function delete(EmployeeId $id): bool;
}
