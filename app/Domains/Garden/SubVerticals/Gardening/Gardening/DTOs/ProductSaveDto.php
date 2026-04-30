<?php

declare(strict_types=1);

namespace App\Domains\Garden\SubVerticals\Gardening\DTOs;

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
        private readonly ?array $specifications = null,
        private readonly bool $isPublished = true,
        /** Plant specific fields (optional if category is plant) **/
        private readonly ?string $botanicalName = null,
        private readonly ?string $hardinessZone = null,
        private readonly ?string $lightRequirement = null,
        private readonly ?string $waterNeeds = null,
        private readonly ?array $careCalendar = null,
        private readonly ?string $correlationId = null
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
