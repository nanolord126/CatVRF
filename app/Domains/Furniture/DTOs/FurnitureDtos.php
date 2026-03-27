<?php

declare(strict_types=1);

namespace App\Domains\Furniture\DTOs;

/**
 * Immutable DTO for creating or updating a furniture product.
 */
final readonly class FurnitureProductDto
{
    public function __construct(
        public string $name,
        public int $storeId,
        public int $categoryId,
        public string $sku,
        public int $priceB2c,
        public int $priceB2b,
        public int $stock,
        public string $description = '',
        public array $properties = [],
        public bool $isOversized = false,
        public bool $requiresAssembly = false,
        public int $assemblyCost = 0,
        public ?string $threeDUrl = null,
        public array $recommendedRooms = [],
        public ?string $correlationId = null,
        public array $tags = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            storeId: (int) $data['furniture_store_id'],
            categoryId: (int) $data['furniture_category_id'],
            sku: $data['sku'],
            priceB2c: (int) $data['price_b2c'],
            priceB2b: (int) $data['price_b2b'],
            stock: (int) ($data['stock_quantity'] ?? 0),
            description: $data['description'] ?? '',
            properties: $data['properties'] ?? [],
            isOversized: (bool) ($data['is_oversized'] ?? false),
            requiresAssembly: (bool) ($data['requires_assembly'] ?? false),
            assemblyCost: (int) ($data['assembly_cost'] ?? 0),
            threeDUrl: $data['threed_preview_url'] ?? null,
            recommendedRooms: $data['recommended_room_types'] ?? [],
            correlationId: $data['correlation_id'] ?? null,
            tags: $data['tags'] ?? []
        );
    }
}

/**
 * DTO for processing a custom AI-driven interior order.
 */
final readonly class FurnitureCustomOrderDto
{
    public function __construct(
        public int $userId,
        public int $roomTypeId,
        public int $totalAmount,
        public array $aiSpecification,
        public array $photoAnalysis = [],
        public bool $includeAssembly = true,
        public ?string $correlationId = null
    ) {}
}

/**
 * Request DTO for AI Interior Constructor.
 */
final readonly class AIInteriorRequestDto
{
    public function __construct(
        public int $roomTypeId,
        public string $stylePreference, // 'modern', 'minimalist', 'loft', etc.
        public int $budgetKopecks,
        public ?string $photoPath = null,
        public array $existingFurnitureIds = [],
        public ?string $correlationId = null
    ) {}
}

/**
 * Result DTO from AI Interior Constructor.
 */
final readonly class AIInteriorResultDto
{
    public function __construct(
        public array $recommendedProductIds,
        public int $estimatedTotal,
        public string $layoutStrategy,
        public array $styleAnalysis,
        public string $correlationId
    ) {}
}
