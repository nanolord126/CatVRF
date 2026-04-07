<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\DTOs;

final readonly class VeganProductCreateDto
{


    public function __construct(
            public string $name,
            public string $sku,
            public string $brand,
            public int $storeId,
            public int $categoryId,
            public int $price,
            private ?int $b2bPrice = null,
            private array $nutritionInfo = [],
            private array $allergenInfo = [],
            private string $ingredients = '',
            private int $initialStock = 0,
            private ?int $shelfLifeDays = null,
            private float $weightGrams = 0.0,
            private ?string $correlationId = null,
            private array $tags = []) {}

        /**
         * Map request data to DTO with validation-ready types.
         */
        public static function fromRequest(array $data, ?string $correlationId = null): self
        {
            return new self(
                name: (string) ($data['name'] ?? ''),
                sku: (string) ($data['sku'] ?? ''),
                brand: (string) ($data['brand'] ?? ''),
                storeId: (int) ($data['vegan_store_id'] ?? 0),
                categoryId: (int) ($data['vegan_category_id'] ?? 0),
                price: (int) ($data['price'] ?? 0),
                b2bPrice: isset($data['b2b_price']) ? (int) $data['b2b_price'] : null,
                nutritionInfo: (array) ($data['nutrition_info'] ?? []),
                allergenInfo: (array) ($data['allergen_info'] ?? []),
                ingredients: (string) ($data['ingredients'] ?? ''),
                initialStock: (int) ($data['current_stock'] ?? 0),
                shelfLifeDays: isset($data['shelf_life_days']) ? (int) $data['shelf_life_days'] : null,
                weightGrams: (float) ($data['weight_grams'] ?? 0.0),
                correlationId: $correlationId,
                tags: (array) ($data['tags'] ?? []),
            );
        }
    }
