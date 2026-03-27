<?php

declare(strict_types=1);

namespace App\Domains\Gardening\DTOs;

/**
 * Gardening DTO Layer (Level 2/9)
 * Strict readonly classes for handling data flow across services and controllers.
 */

/**
 * Product Save Request DTO
 */
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
        public ?array $specifications = null,
        public bool $isPublished = true,
        /** Plant specific fields (optional if category is plant) **/
        public ?string $botanicalName = null,
        public ?string $hardinessZone = null,
        public ?string $lightRequirement = null,
        public ?string $waterNeeds = null,
        public ?array $careCalendar = null,
        public ?string $correlationId = null
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

/**
 * AI Recommendation Request DTO
 * Used for building personal garden plans and climate-based advice.
 */
final readonly class GardenAIRequestDto
{
    public function __construct(
        public int $userId,
        public string $climateZone, // "Hardiness Zone 5b", etc.
        public string $plotType, // "Balcony", "Small Backyard", "Large Greenhouse"
        public array $interests, // ["Vegetables", "Flowers", "Sustainability"]
        public ?string $photoBase64 = null, // Optional for Computer Vision
        public string $correlationId = ""
    ) {}
}

/**
 * Subscription Box Update DTO
 */
final readonly class SubscriptionBoxDto
{
    public function __construct(
        public string $name,
        public string $frequency,
        public int $price,
        public array $contents,
        public bool $isActive = true,
        public ?string $correlationId = null
    ) {}
}

/**
 * Review Submission DTO
 */
final readonly class ReviewInputDto
{
    public function __construct(
        public int $productId,
        public int $userId,
        public int $rating,
        public string $comment,
        public ?array $growthHistory = null,
        public string $correlationId = ""
    ) {}
}

/**
 * Enums representing Gardening constraints (Alternative to strictly backed enums)
 */
final class GardenEnums
{
    public const HARDINESS_ZONES = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11'];
    public const LIGHT_REQ = ['full_sun', 'partial_shade', 'shade'];
    public const WATER_NEEDS = ['low', 'medium', 'high'];
    public const BOX_FREQUENCIES = ['monthly', 'quarterly', 'seasonal'];

    public const GARDEN_VERTICAL_CODE = 'GARDEN_2026';
}
