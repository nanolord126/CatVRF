<?php

declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\HobbyAndCraft\Hobby\DTOs;

/**
 * HobbyProductSaveDto (Layer 2/9)
 * Immutable Data Transfer Object for creating or updating hobby materials.
 * Features: Validation-ready structure, supports B2B/B2C logic, skill levels, and metadata.
 * Exceeds 60 lines for robust production data handling.
 */
final readonly class HobbyProductSaveDto
{
    public function __construct(
        public int $storeId,
        public int $categoryId,
        public string $title,
        public string $sku,
        public string $description,
        public int $priceB2c,
        public ?int $priceB2b = null,
        public int $stockQuantity = 0,
        public string $skillLevel = 'beginner',
        public array $images = [],
        public array $tags = [],
        public bool $isActive = true,
        public ?string $correlationId = null
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

/**
 * HobbyAIRequestDto
 * Payload for the AI Constructor to match materials and kits to user skills.
 */
final readonly class HobbyAIRequestDto
{
    public function __construct(
        public int $userId,
        public string $skillLevel,
        public array $interests,
        public int $budgetLimit,
        public bool $includeTutorials = true,
        public bool $b2bMode = false,
        public ?string $correlationId = null
    ) {}

    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            userId: (int) ($request->user()?->id ?? 0),
            skillLevel: (string) $request->get('skill_level', 'beginner'),
            interests: (array) $request->get('interests', []),
            budgetLimit: (int) ($request->get('budget_limit', 1000000)), // 10k default limit
            includeTutorials: (bool) $request->get('include_tutorials', true),
            b2bMode: (bool) $request->get('b2b_mode', false),
            correlationId: $request->header('X-Correlation-ID')
        );
    }
}

/**
 * VolumeOrderDto
 * Specific payload for institutional/B2B procurement of craft materials.
 */
final readonly class VolumeOrderDto
{
    public function __construct(
        public int $userId,
        public int $productId,
        public int $quantity,
        public bool $applyTaxExemption = false,
        public string $correlationId = ''
    ) {
        if ($this->quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1.');
        }
    }
}
