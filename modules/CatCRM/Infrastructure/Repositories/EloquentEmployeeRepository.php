<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Repositories;

use Modules\CatCRM\Domain\Staff\Repositories\EmployeeRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Employee;
use Modules\CatCRM\Domain\Staff\ValueObjects\EmployeeId;
use Modules\CatCRM\Domain\Staff\ValueObjects\EmployeeStatus;
use Modules\CatCRM\Domain\Staff\ValueObjects\EmployeeRole;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * EloquentEmployeeRepository — Layer 7: Repository Implementation
 * 
 * Implements EmployeeRepositoryInterface using Eloquent ORM
 * Part of 9-layer architecture
 */
final class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    public function findById(EmployeeId $id): ?Employee
    {
        $record = DB::table('staff_employees')->where('id', $id->value)->first();
        
        if (!$record) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function findByUserId(int $userId, int $tenantId): ?Employee
    {
        $record = DB::table('staff_employees')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();
        
        if (!$record) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function findByTenant(int $tenantId): array
    {
        $records = DB::table('staff_employees')
            ->where('tenant_id', $tenantId)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findByDepartment(int $tenantId, string $department): array
    {
        $records = DB::table('staff_employees')
            ->where('tenant_id', $tenantId)
            ->where('department', $department)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findByManager(int $tenantId, int $managerId): array
    {
        $records = DB::table('staff_employees')
            ->where('tenant_id', $tenantId)
            ->where('manager_id', $managerId)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function save(Employee $employee): bool
    {
        $data = [
            'tenant_id' => $employee->tenantId,
            'user_id' => $employee->userId,
            'first_name' => $employee->firstName,
            'last_name' => $employee->lastName,
            'middle_name' => $employee->middleName,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'position' => $employee->position,
            'department' => $employee->department,
            'role' => $employee->role->value,
            'status' => $employee->status->value,
            'manager_id' => $employee->managerId,
            'hire_date' => $employee->hireDate->toDateTimeString(),
            'termination_date' => $employee->terminationDate?->toDateTimeString(),
            'avatar' => $employee->avatar,
            'skills' => json_encode($employee->skills),
            'level' => $employee->level,
            'experience_points' => $employee->experiencePoints,
            'performance_score' => $employee->performanceScore,
            'burnout_risk' => $employee->burnoutRisk,
            'slack_id' => $employee->slackId,
            'teams_id' => $employee->teamsId,
            'metadata' => json_encode($employee->metadata),
            'updated_at' => $employee->updatedAt->toDateTimeString(),
        ];

        if ($employee->id->value === 0) {
            // Insert
            $data['created_at'] = $employee->createdAt->toDateTimeString();
            $id = DB::table('staff_employees')->insertGetId($data);
            return $id > 0;
        } else {
            // Update
            return DB::table('staff_employees')
                ->where('id', $employee->id->value)
                ->update($data) > 0;
        }
    }

    public function delete(EmployeeId $id): bool
    {
        return DB::table('staff_employees')
            ->where('id', $id->value)
            ->delete() > 0;
    }

    private function mapToEntity(array $record): Employee
    {
        return new Employee(
            id: EmployeeId::fromInt((int) $record['id']),
            tenantId: (int) $record['tenant_id'],
            userId: (int) $record['user_id'],
            firstName: $record['first_name'],
            lastName: $record['last_name'],
            middleName: $record['middle_name'] ?? null,
            email: $record['email'],
            phone: $record['phone'],
            position: $record['position'] ?? null,
            department: $record['department'] ?? null,
            role: EmployeeRole::from($record['role']),
            status: EmployeeStatus::from($record['status']),
            managerId: $record['manager_id'] ? (int) $record['manager_id'] : null,
            hireDate: CarbonImmutable::parse($record['hire_date']),
            terminationDate: $record['termination_date'] ? CarbonImmutable::parse($record['termination_date']) : null,
            avatar: $record['avatar'] ?? null,
            skills: json_decode($record['skills'] ?? '[]', true),
            level: (int) $record['level'],
            experiencePoints: (int) $record['experience_points'],
            performanceScore: (float) $record['performance_score'],
            burnoutRisk: (float) $record['burnout_risk'],
            slackId: $record['slack_id'] ?? null,
            teamsId: $record['teams_id'] ?? null,
            metadata: json_decode($record['metadata'] ?? '{}', true),
            createdAt: CarbonImmutable::parse($record['created_at']),
            updatedAt: CarbonImmutable::parse($record['updated_at']),
        );
    }
}
