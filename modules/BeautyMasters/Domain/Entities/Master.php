<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

final readonly class Master
{
    public function __construct(
        public int $id,
        public int $venueId,
        public ?int $userId,
        public string $firstName,
        public string $lastName,
        public ?string $patronymic,
        public string $slug,
        public ?string $avatar,
        public ?string $bio,
        public ?array $specializations,
        public ?array $certifications,
        public int $experienceYears,
        public float $rating,
        public int $totalReviews,
        public bool $isActive,
        public bool $isMobileMaster,
        public float $baseCommissionRate,
        public ?array $workingPreferences,
        public ?\DateTimeImmutable $hiredAt,
        public ?\DateTimeImmutable $firedAt,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
        public ?\DateTimeImmutable $deletedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            venueId: $data['venue_id'],
            userId: $data['user_id'] ?? null,
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            patronymic: $data['patronymic'] ?? null,
            slug: $data['slug'],
            avatar: $data['avatar'] ?? null,
            bio: $data['bio'] ?? null,
            specializations: $data['specializations'] ?? null,
            certifications: $data['certifications'] ?? null,
            experienceYears: (int) $data['experience_years'],
            rating: (float) $data['rating'],
            totalReviews: (int) $data['total_reviews'],
            isActive: (bool) $data['is_active'],
            isMobileMaster: (bool) $data['is_mobile_master'],
            baseCommissionRate: (float) $data['base_commission_rate'],
            workingPreferences: $data['working_preferences'] ?? null,
            hiredAt: $data['hired_at'] ? new \DateTimeImmutable($data['hired_at']) : null,
            firedAt: $data['fired_at'] ? new \DateTimeImmutable($data['fired_at']) : null,
            createdAt: new \DateTimeImmutable($data['created_at']),
            updatedAt: $data['updated_at'] ? new \DateTimeImmutable($data['updated_at']) : null,
            deletedAt: $data['deleted_at'] ? new \DateTimeImmutable($data['deleted_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'venue_id' => $this->venueId,
            'user_id' => $this->userId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'patronymic' => $this->patronymic,
            'slug' => $this->slug,
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'specializations' => $this->specializations,
            'certifications' => $this->certifications,
            'experience_years' => $this->experienceYears,
            'rating' => $this->rating,
            'total_reviews' => $this->totalReviews,
            'is_active' => $this->isActive,
            'is_mobile_master' => $this->isMobileMaster,
            'base_commission_rate' => $this->baseCommissionRate,
            'working_preferences' => $this->workingPreferences,
            'hired_at' => $this->hiredAt?->format('Y-m-d H:i:s'),
            'fired_at' => $this->firedAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }

    public function getFullName(): string
    {
        $name = trim("{$this->firstName} {$this->lastName}");
        if ($this->patronymic) {
            $name .= " {$this->patronymic}";
        }
        return $name;
    }
}
