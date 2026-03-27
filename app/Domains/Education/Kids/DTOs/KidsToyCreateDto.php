<?php

declare(strict_types=1);

namespace App\Domains\Education\Kids\DTOs;

/**
 * KidsToyCreateDto - Immutable Data Object for adding toy metadata.
 * Layer: DTOs (2/9)
 */
final readonly class KidsToyCreateDto
{
    /**
     * @param array<string, bool> $educational_goals
     * @param array<string> $safety_certificates
     */
    public function __construct(
        public int $product_id,
        public string $toy_type, // puzzle, plush, active, board, construction
        public string $material_type, // plastic, wood, textile
        public bool $has_batteries,
        public ?string $battery_type,
        public array $educational_goals,
        public array $safety_certificates,
        public string $brand_name,
        public ?string $correlation_id = null,
    ) {}

    /**
     * Create from request.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            product_id: (int) $data['product_id'],
            toy_type: $data['toy_type'],
            material_type: $data['material_type'],
            has_batteries: (bool) ($data['has_batteries'] ?? false),
            battery_type: $data['battery_type'] ?? null,
            educational_goals: $data['educational_goals'] ?? [],
            safety_certificates: $data['safety_certificates'] ?? [],
            brand_name: $data['brand_name'] ?? 'Generic',
            correlation_id: $data['correlation_id'] ?? null,
        );
    }

    /**
     * Convert to base array.
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->product_id,
            'toy_type' => $this->toy_type,
            'material_type' => $this->material_type,
            'has_batteries' => $this->has_batteries,
            'battery_type' => $this->battery_type,
            'educational_goals' => $this->educational_goals,
            'safety_certificates' => $this->safety_certificates,
            'brand_name' => $this->brand_name,
            'correlation_id' => $this->correlation_id,
        ];
    }
}
