<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Modules\Warehouse\Domain\ValueObjects\ProductId;

final readonly class Product
{
    public function __construct(
        private ProductId $id,
        private string $sku,
        private string $name,
        private ?string $barcode,
        private ?string $description,
        private string $unit,
        private float $weight,
        private ?string $weightUnit,
        private ?array $dimensions,
        private bool $isHazardous,
        private bool $isFragile,
        private bool $requiresTemperatureControl,
        private ?float $minTemperature,
        private ?float $maxTemperature,
        private ?string $category,
        private ?string $brand,
        private bool $isActive,
        private \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null
    ) {}

    public static function create(
        string $sku,
        string $name,
        string $unit = 'шт',
        float $weight = 0.0,
        ?string $barcode = null,
        ?string $description = null,
        ?string $category = null,
        ?string $brand = null
    ): self {
        return new self(
            id: ProductId::generate(),
            sku: $sku,
            name: $name,
            barcode: $barcode,
            description: $description,
            unit: $unit,
            weight: $weight,
            weightUnit: 'kg',
            dimensions: null,
            isHazardous: false,
            isFragile: false,
            requiresTemperatureControl: false,
            minTemperature: null,
            maxTemperature: null,
            category: $category,
            brand: $brand,
            isActive: true,
            createdAt: new \DateTimeImmutable()
        );
    }

    public function getId(): ProductId
    {
        return $this->id;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBarcode(): ?string
    {
        return $this->barcode;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function getWeightUnit(): ?string
    {
        return $this->weightUnit;
    }

    public function getDimensions(): ?array
    {
        return $this->dimensions;
    }

    public function isHazardous(): bool
    {
        return $this->isHazardous;
    }

    public function isFragile(): bool
    {
        return $this->isFragile;
    }

    public function requiresTemperatureControl(): bool
    {
        return $this->requiresTemperatureControl;
    }

    public function getMinTemperature(): ?float
    {
        return $this->minTemperature;
    }

    public function getMaxTemperature(): ?float
    {
        return $this->maxTemperature;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function activate(): self
    {
        return new self(
            id: $this->id,
            sku: $this->sku,
            name: $this->name,
            barcode: $this->barcode,
            description: $this->description,
            unit: $this->unit,
            weight: $this->weight,
            weightUnit: $this->weightUnit,
            dimensions: $this->dimensions,
            isHazardous: $this->isHazardous,
            isFragile: $this->isFragile,
            requiresTemperatureControl: $this->requiresTemperatureControl,
            minTemperature: $this->minTemperature,
            maxTemperature: $this->maxTemperature,
            category: $this->category,
            brand: $this->brand,
            isActive: true,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            sku: $this->sku,
            name: $this->name,
            barcode: $this->barcode,
            description: $this->description,
            unit: $this->unit,
            weight: $this->weight,
            weightUnit: $this->weightUnit,
            dimensions: $this->dimensions,
            isHazardous: $this->isHazardous,
            isFragile: $this->isFragile,
            requiresTemperatureControl: $this->requiresTemperatureControl,
            minTemperature: $this->minTemperature,
            maxTemperature: $this->maxTemperature,
            category: $this->category,
            brand: $this->brand,
            isActive: false,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'sku' => $this->sku,
            'name' => $this->name,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'unit' => $this->unit,
            'weight' => $this->weight,
            'weight_unit' => $this->weightUnit,
            'dimensions' => $this->dimensions,
            'is_hazardous' => $this->isHazardous,
            'is_fragile' => $this->isFragile,
            'requires_temperature_control' => $this->requiresTemperatureControl,
            'min_temperature' => $this->minTemperature,
            'max_temperature' => $this->maxTemperature,
            'category' => $this->category,
            'brand' => $this->brand,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
