<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\ValueObjects;

final readonly class Address
{
    private function __construct(
        public string $fullAddress,
        public ?string $city,
        public ?string $street,
        public ?string $house,
        public ?string $apartment,
        public ?string $postalCode,
        public ?float $latitude,
        public ?float $longitude,
    ) {}

    public static function create(string $fullAddress): self
    {
        return new self(
            fullAddress: $fullAddress,
            city: null,
            street: null,
            house: null,
            apartment: null,
            postalCode: null,
            latitude: null,
            longitude: null,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            fullAddress: $data['full_address'] ?? $data['address'] ?? '',
            city: $data['city'] ?? null,
            street: $data['street'] ?? null,
            house: $data['house'] ?? null,
            apartment: $data['apartment'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
        );
    }

    public function withCoordinates(float $latitude, float $longitude): self
    {
        return new self(
            fullAddress: $this->fullAddress,
            city: $this->city,
            street: $this->street,
            house: $this->house,
            apartment: $this->apartment,
            postalCode: $this->postalCode,
            latitude: $latitude,
            longitude: $longitude,
        );
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function formatShort(): string
    {
        $parts = array_filter([
            $this->city,
            $this->street,
            $this->house,
        ]);

        return implode(', ', $parts);
    }

    public function formatFull(): string
    {
        $parts = array_filter([
            $this->postalCode,
            $this->city,
            $this->street,
            $this->house,
            $this->apartment ? 'кв. ' . $this->apartment : null,
        ]);

        return implode(', ', $parts);
    }

    public function toArray(): array
    {
        return [
            'full_address' => $this->fullAddress,
            'city' => $this->city,
            'street' => $this->street,
            'house' => $this->house,
            'apartment' => $this->apartment,
            'postal_code' => $this->postalCode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
