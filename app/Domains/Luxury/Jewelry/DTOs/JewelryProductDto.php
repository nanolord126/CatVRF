<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\DTOs;

final readonly class JewelryProductDto
{
    public function __construct(
        public string $name,
        public string $sku,
        public int $storeId,
        public int $categoryId,
        public int $priceB2c,
        public int $priceB2b,
        public int $stockQuantity,
        public string $metalType,
        public string $metalFineness,
        private readonly ?float $weightGrams = null,
        private readonly ?array $gemstones = null,
        private readonly ?int $collectionId = null,
        private readonly bool $hasCertification = false,
        private readonly ?string $certificateNumber = null,
        private readonly bool $isCustomizable = false,
        private readonly bool $isGiftWrapped = false,
        private readonly bool $isPublished = false,
        private readonly ?array $tags = null,
        private readonly ?string $correlationId = null
    ) {}

    public static function fromRequest(array $data, ?string $correlationId = null): self
    {
        return new self(
            name: $data['name'],
            sku: $data['sku'],
            storeId: (int) $data['store_id'],
            categoryId: (int) $data['category_id'],
            priceB2c: (int) $data['price_b2c'],
            priceB2b: (int) $data['price_b2b'],
            stockQuantity: (int) $data['stock_quantity'],
            metalType: $data['metal_type'],
            metalFineness: $data['metal_fineness'],
            weightGrams: isset($data['weight_grams']) ? (float) $data['weight_grams'] : null,
            gemstones: $data['gemstones'] ?? null,
            collectionId: isset($data['collection_id']) ? (int) $data['collection_id'] : null,
            hasCertification: (bool) ($data['has_certification'] ?? false),
            certificateNumber: $data['certificate_number'] ?? null,
            isCustomizable: (bool) ($data['is_customizable'] ?? false),
            isGiftWrapped: (bool) ($data['is_gift_wrapped'] ?? false),
            isPublished: (bool) ($data['is_published'] ?? false),
            tags: $data['tags'] ?? null,
            correlationId: $correlationId
        );
    }
}
