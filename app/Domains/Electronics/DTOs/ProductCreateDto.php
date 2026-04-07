<?php

declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

final readonly class ProductCreateDto
{


    /**
         * @param array<string, mixed> $specs
         * @param array<string> $tags
         */
        public function __construct(
            public int $categoryId,
            public int $storeId,
            public string $name,
            public string $sku,
            public string $brand,
            public string $modelNumber,
            public int $priceKopecks,
            private ?int $b2bPriceKopecks = null,
            private int $initialStock = 0,
            private string $availability = 'in_stock',
            private array $specs = [],
            private array $tags = [],
            private string $correlationId = '',
            private ?float $weightKg = null) {}

        /**
         * Create from array or request.
         */
        public static function fromArray(array $data): self
        {
            return new self(
                categoryId: (int) $data['category_id'],
                storeId: (int) $data['store_id'],
                name: (string) $data['name'],
                sku: (string) $data['sku'],
                brand: (string) $data['brand'],
                modelNumber: (string) ($data['model_number'] ?? ''),
                priceKopecks: (int) $data['price_kopecks'],
                b2bPriceKopecks: isset($data['b2b_price_kopecks']) ? (int) $data['b2b_price_kopecks'] : null,
                initialStock: (int) ($data['initial_stock'] ?? 0),
                availability: (string) ($data['availability'] ?? 'in_stock'),
                specs: (array) ($data['specs'] ?? []),
                tags: (array) ($data['tags'] ?? []),
                correlationId: (string) ($data['correlation_id'] ?? ''),
                weightKg: isset($data['weight_kg']) ? (float) $data['weight_kg'] : null,
            );
        }
    }
