<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для обновления VerticalItem в вертикали VerticalName.
 *
 * CANON 2026 — Layer 2: DTOs.
 * Все свойства public readonly (PHP 8.3+), строгая типизация.
 * Nullable-поля означают «не менять» при обновлении.
 *
 * @package App\Domains\VerticalName\DTOs
 */
final readonly class UpdateVerticalItemDto
{
    public function __construct(
        public int $itemId,
        public int $tenantId,
        public ?int $businessGroupId,
        public string $correlationId,
        public ?string $name = null,
        public ?string $description = null,
        public ?int $priceKopecks = null,
        public ?string $sku = null,
        public ?string $category = null,
        public ?int $stockQuantity = null,
        public ?bool $isActive = null,
        public ?bool $isB2bAvailable = null,
        public ?string $imageUrl = null,
        public ?array $tags = null,
        public ?array $metadata = null,
        public bool $isB2B = false,
    ) {
    }

    /**
     * Гидрация из HTTP-запроса.
     *
     * CANON 2026: correlation_id из заголовка или автогенерация.
     */
    public static function from(Request $request, int $itemId): self
    {
        $validated = $request->validated();

        return new self(
            itemId: $itemId,
            tenantId: (int) tenant()?->id,
            businessGroupId: $request->input('business_group_id')
                ? (int) $request->input('business_group_id')
                : null,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
            name: $validated['name'] ?? null,
            description: $validated['description'] ?? null,
            priceKopecks: isset($validated['price_kopecks']) ? (int) $validated['price_kopecks'] : null,
            sku: $validated['sku'] ?? null,
            category: $validated['category'] ?? null,
            stockQuantity: isset($validated['stock_quantity']) ? (int) $validated['stock_quantity'] : null,
            isActive: isset($validated['is_active']) ? (bool) $validated['is_active'] : null,
            isB2bAvailable: isset($validated['is_b2b_available']) ? (bool) $validated['is_b2b_available'] : null,
            imageUrl: $validated['image_url'] ?? null,
            tags: $validated['tags'] ?? null,
            metadata: $validated['metadata'] ?? null,
            isB2B: $request->has('inn') && $request->has('business_card_id'),
        );
    }

    /**
     * Конвертация в массив — только непустые значения для partial update.
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->priceKopecks !== null) {
            $data['price_kopecks'] = $this->priceKopecks;
        }

        if ($this->sku !== null) {
            $data['sku'] = $this->sku;
        }

        if ($this->category !== null) {
            $data['category'] = $this->category;
        }

        if ($this->stockQuantity !== null) {
            $data['stock_quantity'] = $this->stockQuantity;
        }

        if ($this->isActive !== null) {
            $data['is_active'] = $this->isActive;
        }

        if ($this->isB2bAvailable !== null) {
            $data['is_b2b_available'] = $this->isB2bAvailable;
        }

        if ($this->imageUrl !== null) {
            $data['image_url'] = $this->imageUrl;
        }

        if ($this->tags !== null) {
            $data['tags'] = $this->tags;
        }

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        $data['correlation_id'] = $this->correlationId;

        return $data;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }
}
