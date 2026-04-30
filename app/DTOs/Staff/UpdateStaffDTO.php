<?php

declare(strict_types=1);

namespace App\DTOs\Staff;

/**
 * UpdateStaffDTO — DTO для обновления сотрудника.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Immutable DTO с валидацией данных для обновления сотрудника.
 * Все поля опциональны.
 */
final readonly class UpdateStaffDTO
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $middleName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $telegram = null,
        public ?string $whatsapp = null,
        public ?string $role = null,
        public ?string $position = null,
        public ?string $department = null,
        public ?int $managerId = null,
        public ?string $hiredAt = null,
        public ?string $probationEndAt = null,
        public ?string $employmentType = null,
        public ?string $schedule = null,
        public ?float $salary = null,
        public ?string $salaryType = null,
        public ?string $status = null,
        public ?array $permissions = null,
        public ?int $userId = null,
        public ?string $photoUrl = null,
        public ?string $notes = null,
        public ?string $performanceNotes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['first_name'] ?? $data['firstName'] ?? null,
            lastName: $data['last_name'] ?? $data['lastName'] ?? null,
            middleName: $data['middle_name'] ?? $data['middleName'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            telegram: $data['telegram'] ?? null,
            whatsapp: $data['whatsapp'] ?? null,
            role: $data['role'] ?? null,
            position: $data['position'] ?? null,
            department: $data['department'] ?? null,
            managerId: $data['manager_id'] ?? $data['managerId'] ?? null,
            hiredAt: $data['hired_at'] ?? $data['hiredAt'] ?? null,
            probationEndAt: $data['probation_end_at'] ?? $data['probationEndAt'] ?? null,
            employmentType: $data['employment_type'] ?? $data['employmentType'] ?? null,
            schedule: $data['schedule'] ?? null,
            salary: $data['salary'] ?? null,
            salaryType: $data['salary_type'] ?? $data['salaryType'] ?? null,
            status: $data['status'] ?? null,
            permissions: $data['permissions'] ?? null,
            userId: $data['user_id'] ?? $data['userId'] ?? null,
            photoUrl: $data['photo_url'] ?? $data['photoUrl'] ?? null,
            notes: $data['notes'] ?? null,
            performanceNotes: $data['performance_notes'] ?? $data['performanceNotes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
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
            'photo_url' => $this->photoUrl,
            'notes' => $this->notes,
            'performance_notes' => $this->performanceNotes,
        ], fn ($value) => $value !== null);
    }
}
