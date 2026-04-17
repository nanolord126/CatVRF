<?php declare(strict_types=1);

namespace App\Domains\UserProfile\DTOs;

final readonly class UpdateProfileDto
{
    public function __construct(
        public int $tenantId,
        public int $userId,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $phone = null,
        public ?string $avatarUrl = null,
        public ?\DateTime $birthDate = null,
        public ?string $gender = null,
        public ?string $preferredLanguage = null,
        public ?string $timezone = null,
        public ?string $bio = null,
        public ?array $metadata = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            userId: $data['user_id'],
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            phone: $data['phone'] ?? null,
            avatarUrl: $data['avatar_url'] ?? null,
            birthDate: isset($data['birth_date']) ? new \DateTime($data['birth_date']) : null,
            gender: $data['gender'] ?? null,
            preferredLanguage: $data['preferred_language'] ?? null,
            timezone: $data['timezone'] ?? null,
            bio: $data['bio'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'avatar_url' => $this->avatarUrl,
            'birth_date' => $this->birthDate?->format('Y-m-d'),
            'gender' => $this->gender,
            'preferred_language' => $this->preferredLanguage,
            'timezone' => $this->timezone,
            'bio' => $this->bio,
            'metadata' => $this->metadata,
        ];
    }
}
