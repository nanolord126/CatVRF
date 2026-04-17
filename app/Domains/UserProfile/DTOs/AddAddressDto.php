<?php declare(strict_types=1);

namespace App\Domains\UserProfile\DTOs;

final readonly class AddAddressDto
{
    public function __construct(
        public int $tenantId,
        public int $userId,
        public string $address,
        public string $type = 'other',
        public ?string $city = null,
        public ?string $region = null,
        public ?string $postalCode = null,
        public ?string $country = null,
        public ?float $lat = null,
        public ?float $lon = null,
        public bool $isDefault = false,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            userId: $data['user_id'],
            address: $data['address'],
            type: $data['type'] ?? 'other',
            city: $data['city'] ?? null,
            region: $data['region'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            country: $data['country'] ?? null,
            lat: $data['lat'] ?? null,
            lon: $data['lon'] ?? null,
            isDefault: $data['is_default'] ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'address' => $this->address,
            'type' => $this->type,
            'city' => $this->city,
            'region' => $this->region,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'lat' => $this->lat,
            'lon' => $this->lon,
            'is_default' => $this->isDefault,
        ];
    }
}
