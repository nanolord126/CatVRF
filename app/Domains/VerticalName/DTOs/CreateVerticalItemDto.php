<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для создания VerticalItem в вертикали VerticalName.
 *
 * CANON 2026 — Layer 2: DTOs.
 * Все свойства public readonly (PHP 8.3+), строгая типизация.
 * Статический from() для гидрации из Request.
 * toArray() для передачи в Eloquent::create().
 *
 * Определение B2B: $request->has('inn') && $request->has('business_card_id').
 *
 * @package App\Domains\VerticalName\DTOs
 */
final readonly class CreateVerticalItemDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public string $correlationId,
        public string $name,
        public ?string $description,
        public int $priceKopecks,
        public ?string $sku,
        public ?string $category,
        public int $stockQuantity,
        public bool $isActive,
        public bool $isB2bAvailable,
        public ?string $imageUrl,
        public ?array $tags,
        public ?array $metadata,
        public ?string $idempotencyKey = null,
        public bool $isB2B = false,
    ) {
    }

    /**
     * Гидрация из HTTP-запроса.
     *
     * CANON 2026: correlation_id берётся из заголовка X-Correlation-ID или генерируется.
     * B2C/B2B определяется по наличию inn + business_card_id.
     */
    public static function from(Request $request): self
    {
        $validated = $request->validated();

        return new self(
            tenantId: (int) tenant()?->id,
            businessGroupId: $request->input('business_group_id')
                ? (int) $request->input('business_group_id')
                : null,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
            name: (string) ($validated['name'] ?? ''),
            description: $validated['description'] ?? null,
            priceKopecks: (int) ($validated['price_kopecks'] ?? 0),
            sku: $validated['sku'] ?? null,
            category: $validated['category'] ?? null,
            stockQuantity: (int) ($validated['stock_quantity'] ?? 0),
            isActive: (bool) ($validated['is_active'] ?? true),
            isB2bAvailable: (bool) ($validated['is_b2b_available'] ?? false),
            imageUrl: $validated['image_url'] ?? null,
            tags: $validated['tags'] ?? null,
            metadata: $validated['metadata'] ?? null,
            idempotencyKey: $request->header('Idempotency-Key'),
            isB2B: $request->has('inn') && $request->has('business_card_id'),
        );
    }

    /**
     * Конвертация в массив для Eloquent::create().
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'correlation_id' => $this->correlationId,
            'name' => $this->name,
            'description' => $this->description,
            'price_kopecks' => $this->priceKopecks,
            'sku' => $this->sku,
            'category' => $this->category,
            'stock_quantity' => $this->stockQuantity,
            'is_active' => $this->isActive,
            'is_b2b_available' => $this->isB2bAvailable,
            'image_url' => $this->imageUrl,
            'tags' => $this->tags,
            'metadata' => $this->metadata,
        ];
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getBusinessGroupId(): ?int
    {
        return $this->businessGroupId;
    }
}
