<?php

declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\Hobby\DTOs;

final readonly class HobbyProductSaveDto
{


    public function __construct(
            public int $storeId,
            public int $categoryId,
            public string $title,
            public string $sku,
            public string $description,
            public int $priceB2c,
            private ?int $priceB2b = null,
            private int $stockQuantity = 0,
            private string $skillLevel = 'beginner',
            private array $images = [],
            private array $tags = [],
            private bool $isActive = true,
            private ?string $correlationId = null
        ) {
            // Validation logic can be added here or via external validators
        }

        /**
         * Map from request data or array.
         */
        public static function fromArray(array $data): self
        {
            return new self(
                storeId: (int) $data['store_id'],
                categoryId: (int) $data['category_id'],
                title: (string) $data['title'],
                sku: (string) ($data['sku'] ?? 'DIY-' . uniqid()),
                description: (string) ($data['description'] ?? ''),
                priceB2c: (int) $data['price_b2c'],
                priceB2b: isset($data['price_b2b']) ? (int) $data['price_b2b'] : null,
                stockQuantity: (int) ($data['stock_quantity'] ?? 0),
                skillLevel: (string) ($data['skill_level'] ?? 'beginner'),
                images: (array) ($data['images'] ?? []),
                tags: (array) ($data['tags'] ?? []),
                isActive: (bool) ($data['is_active'] ?? true),
                correlationId: (string) ($data['correlation_id'] ?? '')
            );
        }
    }
