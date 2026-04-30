<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\DTOs\Staff;

use Carbon\CarbonImmutable;

/**
 * CreateEmployeeDTO — DTO для создания сотрудника
 * 
 * Immutable DTO following CatVRF rules
 */
final readonly class CreateEmployeeDTO
{
    public function __construct(
        public int $tenantId,
        public int $userId,
        public string $firstName,
        public string $lastName,
        public ?string $middleName,
        public string $email,
        public string $phone,
        public ?string $position,
        public ?string $department,
        public string $role,
        public ?int $managerId,
        public ?string $slackId,
        public ?string $teamsId,
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            userId: $data['user_id'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            middleName: $data['middle_name'] ?? null,
            email: $data['email'],
            phone: $data['phone'],
            position: $data['position'] ?? null,
            department: $data['department'] ?? null,
            role: $data['role'],
            managerId: $data['manager_id'] ?? null,
            slackId: $data['slack_id'] ?? null,
            teamsId: $data['teams_id'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'middle_name' => $this->middleName,
            'email' => $this->email,
            'phone' => $this->phone,
            'position' => $this->position,
            'department' => $this->department,
            'role' => $this->role,
            'manager_id' => $this->managerId,
            'slack_id' => $this->slackId,
            'teams_id' => $this->teamsId,
            'metadata' => $this->metadata,
        ];
    }
}
