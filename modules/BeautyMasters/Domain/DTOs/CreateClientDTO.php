<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\DTOs;

use readonly;

final readonly class CreateClientDTO
{
    public function __construct(
        public ?int $userId,
        public ?int $venueId,
        public string $firstName,
        public string $lastName,
        public string $phone,
        public ?string $email,
        public ?\DateTimeImmutable $birthDate,
        public ?string $gender,
        public ?string $notes,
        public ?array $allergies,
        public ?array $preferences,
        public ?array $skinType,
        public ?array $hairType,
        public ?string $avatar,
        public bool $allowMarketing,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'] ?? null,
            venueId: $data['venue_id'] ?? null,
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            phone: $data['phone'],
            email: $data['email'] ?? null,
            birthDate: $data['birth_date'] ? new \DateTimeImmutable($data['birth_date']) : null,
            gender: $data['gender'] ?? null,
            notes: $data['notes'] ?? null,
            allergies: $data['allergies'] ?? null,
            preferences: $data['preferences'] ?? null,
            skinType: $data['skin_type'] ?? null,
            hairType: $data['hair_type'] ?? null,
            avatar: $data['avatar'] ?? null,
            allowMarketing: (bool) ($data['allow_marketing'] ?? true),
        );
    }

    public function getFullName(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'venue_id' => $this->venueId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'email' => $this->email,
            'birth_date' => $this->birthDate?->format('Y-m-d'),
            'gender' => $this->gender,
            'notes' => $this->notes,
            'allergies' => $this->allergies,
            'preferences' => $this->preferences,
            'skin_type' => $this->skinType,
            'hair_type' => $this->hairType,
            'avatar' => $this->avatar,
            'allow_marketing' => $this->allowMarketing,
        ];
    }
}
