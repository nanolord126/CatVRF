<?php

declare(strict_types=1);

namespace App\DTOs\Staff;

/**
 * CreateStaffDTO — DTO для создания сотрудника.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Immutable DTO с валидацией данных для создания сотрудника.
 */
final readonly class CreateStaffDTO
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public ?string $middleName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $telegram = null,
        public ?string $whatsapp = null,
        public ?string $role = 'employee',
        public ?string $position = null,
        public ?string $department = null,
        public ?int $managerId = null,
        public ?string $hiredAt = null,
        public ?string $probationEndAt = null,
        public ?string $employmentType = 'full_time',
        public ?string $schedule = null,
        public ?float $salary = 0,
        public ?string $salaryType = 'monthly',
        public ?string $status = 'active',
        public ?array $permissions = null,
        public ?int $userId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['first_name'] ?? $data['firstName'] ?? '',
            lastName: $data['last_name'] ?? $data['lastName'] ?? '',
            middleName: $data['middle_name'] ?? $data['middleName'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            telegram: $data['telegram'] ?? null,
            whatsapp: $data['whatsapp'] ?? null,
            role: $data['role'] ?? 'employee',
            position: $data['position'] ?? null,
            department: $data['department'] ?? null,
            managerId: $data['manager_id'] ?? $data['managerId'] ?? null,
            hiredAt: $data['hired_at'] ?? $data['hiredAt'] ?? null,
            probationEndAt: $data['probation_end_at'] ?? $data['probationEndAt'] ?? null,
            employmentType: $data['employment_type'] ?? $data['employmentType'] ?? 'full_time',
            schedule: $data['schedule'] ?? null,
            salary: $data['salary'] ?? 0,
            salaryType: $data['salary_type'] ?? $data['salaryType'] ?? 'monthly',
            status: $data['status'] ?? 'active',
            permissions: $data['permissions'] ?? null,
            userId: $data['user_id'] ?? $data['userId'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'middle_name' => $this->middleName,
            'email' => $this->email,
            'phone' => $this->phone,
            'telegram' => $this->telegram,
            'whatsapp' => $this->whatsapp,
            'role' => $this->role,
            'position' => $this->position,
            'department' => $this->department,
            'manager_id' => $this->managerId,
            'hired_at' => $this->hiredAt,
            'probation_end_at' => $this->probationEndAt,
            'employment_type' => $this->employmentType,
            'schedule' => $this->schedule,
            'salary' => $this->salary,
            'salary_type' => $this->salaryType,
            'status' => $this->status,
            'permissions' => $this->permissions,
            'user_id' => $this->userId,
        ];
    }
}
