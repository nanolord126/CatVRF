<?php

declare(strict_types=1);

namespace App\Domains\Gardening\DTOs;

final readonly class ProductSaveDto
{


    public function __construct(
            public int $storeId,
            public int $categoryId,
            public string $name,
            public string $sku,
            public int $priceB2c,
            public int $priceB2b,
            public int $stockQuantity,
            private ?array $specifications = null,
            private bool $isPublished = true,
            /** Plant specific fields (optional if category is plant) **/
            private ?string $botanicalName = null,
            private ?string $hardinessZone = null,
            private ?string $lightRequirement = null,
            private ?string $waterNeeds = null,
            private ?array $careCalendar = null,
            private ?string $correlationId = null
        ) {}

        public function toArray(): array
        {
            return [
                'store_id' => $this->storeId,
                'category_id' => $this->categoryId,
                'name' => $this->name,
                'sku' => $this->sku,
                'price_b2c' => $this->priceB2c,
                'price_b2b' => $this->priceB2b,
                'stock_quantity' => $this->stockQuantity,
                'specifications' => $this->specifications,
                'is_published' => $this->isPublished,
                'correlation_id' => $this->correlationId,
            ];
        }
    }
