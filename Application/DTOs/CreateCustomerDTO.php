<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\DTOs;

/**
 * DTO for creating a customer
 */
final readonly class CreateCustomerDTO
{
    public function __construct(
        public ?int $userId,
        public string $type,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $middleName,
        public ?string $companyName,
        public ?string $inn,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public ?string $city,
        public ?string $country,
        public ?string $postalCode,
        public ?string $birthDate,
        public ?string $gender,
        public ?string $source,
        public array $preferences = [],
        public array $communicationPreferences = [],
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'] ?? null,
            type: $data['type'] ?? 'individual',
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            middleName: $data['middle_name'] ?? null,
            companyName: $data['company_name'] ?? null,
            inn: $data['inn'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            country: $data['country'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            birthDate: $data['birth_date'] ?? null,
            gender: $data['gender'] ?? null,
            source: $data['source'] ?? 'supermarket',
            preferences: $data['preferences'] ?? [],
            communicationPreferences: $data['communication_preferences'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'type' => $this->type,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'middle_name' => $this->middleName,
            'company_name' => $this->companyName,
            'inn' => $this->inn,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'postal_code' => $this->postalCode,
            'birth_date' => $this->birthDate,
            'gender' => $this->gender,
            'source' => $this->source,
            'preferences' => $this->preferences,
            'communication_preferences' => $this->communicationPreferences,
            'metadata' => $this->metadata,
        ];
    }
}
