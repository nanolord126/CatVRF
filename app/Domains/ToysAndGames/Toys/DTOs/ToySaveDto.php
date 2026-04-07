<?php

declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\DTOs;

final readonly class ToySaveDto
{


    public function __construct(
            public int $tenantId,
            public int $storeId,
            public int $categoryId,
            public int $ageGroupId,
            public string $title,
            public string $sku,
            public string $description,
            public int $priceB2c, // in kopecks
            public int $priceB2b, // in kopecks
            public int $stockQuantity,
            public string $safetyCertification,
            public string $materialType,
            private bool $isGiftWrappable = true,
            private bool $isActive = true,
            private array $tags = [],
            private array $metadata = [],
            private ?string $correlationId = null
        ) {}

        /**
         * Factory from raw request data.
         */
        public static function fromArray(array $data, int $tenantId, string $cid): self
        {
            return new self(
                tenantId: $tenantId,
                storeId: (int) ($data['store_id'] ?? 0),
                categoryId: (int) ($data['category_id'] ?? 0),
                ageGroupId: (int) ($data['age_group_id'] ?? 0),
                title: (string) ($data['title'] ?? ''),
                sku: (string) ($data['sku'] ?? ''),
                description: (string) ($data['description'] ?? ''),
                priceB2c: (int) ($data['price_b2c'] ?? 0),
                priceB2b: (int) ($data['price_b2b'] ?? 0),
                stockQuantity: (int) ($data['stock_quantity'] ?? 0),
                safetyCertification: (string) ($data['safety_certification'] ?? ''),
                materialType: (string) ($data['material_type'] ?? 'unknown'),
                isGiftWrappable: (bool) ($data['is_gift_wrappable'] ?? true),
                isActive: (bool) ($data['is_active'] ?? true),
                tags: (array) ($data['tags'] ?? []),
                metadata: (array) ($data['metadata'] ?? []),
                correlationId: $cid
            );
        }
    }
