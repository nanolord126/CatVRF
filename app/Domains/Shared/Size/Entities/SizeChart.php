<?php

declare(strict_types=1);

namespace App\Domains\Shared\Size\Entities;

use App\Domains\Shared\Size\ValueObjects\Gender;
use App\Domains\Shared\Size\ValueObjects\Region;
use App\Domains\Shared\Size\ValueObjects\SizeSystem;

final readonly class SizeChart
{
    private function __construct(
        private string $uuid,
        private string $brand,
        private Gender $gender,
        private Region $region,
        private SizeSystem $sizeSystem,
        private array $sizeMappings,
        private array $measurements,
        private ?string $category, // 'fashion' or 'footwear'
        private bool $isActive,
    ) {}

    public static function create(
        string $brand,
        Gender $gender,
        Region $region,
        SizeSystem $sizeSystem,
        array $sizeMappings,
        array $measurements,
        ?string $category = null,
    ): self {
        return new self(
            uuid: (string) \Illuminate\Support\Str::uuid(),
            brand: $brand,
            gender: $gender,
            region: $region,
            sizeSystem: $sizeSystem,
            sizeMappings: $sizeMappings,
            measurements: $measurements,
            category: $category,
            isActive: true,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            uuid: $data['uuid'],
            brand: $data['brand'],
            gender: Gender::from($data['gender']),
            region: Region::from($data['region']),
            sizeSystem: SizeSystem::from($data['size_system']),
            sizeMappings: $data['size_mappings'],
            measurements: $data['measurements'],
            category: $data['category'] ?? null,
            isActive: $data['is_active'],
        );
    }

    public function findSizeByMeasurement(string $measurementType, float $value): ?string
    {
        foreach ($this->measurements as $size => $measurements) {
            if (isset($measurements[$measurementType])) {
                $range = $measurements[$measurementType];
                if (is_array($range)) {
                    if ($value >= $range['min'] && $value <= $range['max']) {
                        return $size;
                    }
                } elseif ($value == $range) {
                    return $size;
                }
            }
        }

        return null;
    }

    public function convertSize(string $size, SizeSystem $targetSystem): ?string
    {
        if (!isset($this->sizeMappings[$size])) {
            return null;
        }

        return $this->sizeMappings[$size][$targetSystem->value] ?? null;
    }

    public function getAvailableSizes(): array
    {
        return array_keys($this->sizeMappings);
    }

    public function isApplicableFor(string $category): bool
    {
        return $this->category === null || $this->category === $category;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function getGender(): Gender
    {
        return $this->gender;
    }

    public function getRegion(): Region
    {
        return $this->region;
    }

    public function getSizeSystem(): SizeSystem
    {
        return $this->sizeSystem;
    }

    public function getSizeMappings(): array
    {
        return $this->sizeMappings;
    }

    public function getMeasurements(): array
    {
        return $this->measurements;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'brand' => $this->brand,
            'gender' => $this->gender->value,
            'region' => $this->region->value,
            'size_system' => $this->sizeSystem->value,
            'size_mappings' => $this->sizeMappings,
            'measurements' => $this->measurements,
            'category' => $this->category,
            'is_active' => $this->isActive,
        ];
    }
}
