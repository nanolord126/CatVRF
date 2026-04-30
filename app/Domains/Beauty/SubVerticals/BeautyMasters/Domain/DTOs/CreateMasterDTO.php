<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\DTOs;

use readonly;

final readonly class CreateMasterDTO
{
    public function __construct(
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
        public bool $isMobileMaster,
        public float $baseCommissionRate,
        public ?array $workingPreferences,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
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
            experienceYears: (int) ($data['experience_years'] ?? 0),
            isMobileMaster: (bool) ($data['is_mobile_master'] ?? false),
            baseCommissionRate: (float) ($data['base_commission_rate'] ?? 30.0),
            workingPreferences: $data['working_preferences'] ?? null,
        );
    }

    public function getFullName(): string
    {
        $name = trim("{$this->firstName} {$this->lastName}");
        if ($this->patronymic) {
            $name .= " {$this->patronymic}";
        }
        return $name;
    }

    public function toArray(): array
    {
        return [
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
            'is_mobile_master' => $this->isMobileMaster,
            'base_commission_rate' => $this->baseCommissionRate,
            'working_preferences' => $this->workingPreferences,
        ];
    }
}
