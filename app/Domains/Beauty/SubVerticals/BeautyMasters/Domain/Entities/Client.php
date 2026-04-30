<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

final readonly class Client
{
    public function __construct(
        public int $id,
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
        public bool $isVip,
        public bool $allowMarketing,
        public ?\DateTimeImmutable $firstVisitAt,
        public ?\DateTimeImmutable $lastVisitAt,
        public int $totalVisits,
        public float $totalSpent,
        public float $averageCheck,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
        public ?\DateTimeImmutable $deletedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
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
            isVip: (bool) $data['is_vip'],
            allowMarketing: (bool) $data['allow_marketing'],
            firstVisitAt: $data['first_visit_at'] ? new \DateTimeImmutable($data['first_visit_at']) : null,
            lastVisitAt: $data['last_visit_at'] ? new \DateTimeImmutable($data['last_visit_at']) : null,
            totalVisits: (int) $data['total_visits'],
            totalSpent: (float) $data['total_spent'],
            averageCheck: (float) $data['average_check'],
            createdAt: new \DateTimeImmutable($data['created_at']),
            updatedAt: $data['updated_at'] ? new \DateTimeImmutable($data['updated_at']) : null,
            deletedAt: $data['deleted_at'] ? new \DateTimeImmutable($data['deleted_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
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
            'is_vip' => $this->isVip,
            'allow_marketing' => $this->allowMarketing,
            'first_visit_at' => $this->firstVisitAt?->format('Y-m-d H:i:s'),
            'last_visit_at' => $this->lastVisitAt?->format('Y-m-d H:i:s'),
            'total_visits' => $this->totalVisits,
            'total_spent' => $this->totalSpent,
            'average_check' => $this->averageCheck,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }

    public function getFullName(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }

    public function isNewClient(): bool
    {
        return $this->totalVisits === 0;
    }

    public function hasAllergy(string $allergy): bool
    {
        return in_array(strtolower($allergy), array_map('strtolower', $this->allergies ?? []), true);
    }
}
