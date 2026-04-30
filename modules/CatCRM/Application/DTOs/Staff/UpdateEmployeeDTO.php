<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\DTOs\Staff;

/**
 * UpdateEmployeeDTO — DTO для обновления сотрудника
 * 
 * Immutable DTO following CatVRF rules
 */
final readonly class UpdateEmployeeDTO
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $middleName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $position = null,
        public ?string $department = null,
        public ?string $role = null,
        public ?int $managerId = null,
        public ?string $status = null,
        public ?string $avatar = null,
        public ?array $skills = null,
        public ?string $slackId = null,
        public ?string $teamsId = null,
        public ?array $metadata = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            middleName: $data['middle_name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            position: $data['position'] ?? null,
            department: $data['department'] ?? null,
            role: $data['role'] ?? null,
            managerId: $data['manager_id'] ?? null,
            status: $data['status'] ?? null,
            avatar: $data['avatar'] ?? null,
            skills: $data['skills'] ?? null,
            slackId: $data['slack_id'] ?? null,
            teamsId: $data['teams_id'] ?? null,
            metadata: $data['metadata'] ?? null,
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
            'position' => $this->position,
            'department' => $this->department,
            'role' => $this->role,
            'manager_id' => $this->managerId,
            'status' => $this->status,
            'avatar' => $this->avatar,
            'skills' => $this->skills,
            'slack_id' => $this->slackId,
            'teams_id' => $this->teamsId,
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }
}
