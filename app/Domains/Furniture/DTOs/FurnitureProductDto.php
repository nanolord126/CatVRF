<?php

declare(strict_types=1);

namespace App\Domains\Furniture\DTOs;

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
            private array $properties = [],
            private bool $isOversized = false,
            private bool $requiresAssembly = false,
            private int $assemblyCost = 0,
            private ?string $threeDUrl = null,
            private array $recommendedRooms = [],
            private ?string $correlationId = null,
            private array $tags = []
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
