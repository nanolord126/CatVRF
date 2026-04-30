<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\Entities;

use Modules\Marketplace\Domain\Enums\ListingStatus;
use Modules\Marketplace\Domain\Enums\ListingType;
use Modules\Marketplace\Domain\ValueObjects\Money;
use Modules\Marketplace\Domain\ValueObjects\Rating;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Ramsey\Uuid\UuidInterface;

/**
 * Агрегированная витринная позиция маркетплейса
 * Объединяет товары/услуги из всех вертикалей с единым интерфейсом
 */
final readonly class ProductListing
{
    private function __construct(
        public UuidInterface $uuid,
        public VerticalSource $source,
        public ListingType $type,
        public ListingStatus $status,
        public string $title,
        public string $description,
        public Money $price,
        public ?Rating $rating,
        public int $reviewCount,
        public int $viewCount,
        public int $orderCount,
        public float $conversionRate,
        public float $popularityScore,
        public float $rankingScore,
        public array $categories,
        public array $tags,
        public array $images,
        public ?string $thumbnail,
        public array $attributes,
        public int $stockQuantity,
        public bool $inStock,
        public bool $isFeatured,
        public bool $isPromoted,
        public ?string $promotionType,
        public ?float $promotionBudget,
        public ?\DateTimeImmutable $promotionStartAt,
        public ?\DateTimeImmutable $promotionEndAt,
        public int $promotionImpressions,
        public int $promotionClicks,
        public int $promotionSpendKopecks,
        public int $promotionPriorityBoost,
        public int $priorityBoost,
        public int $tenantId,
        public int $businessGroupId,
        public int $sourceId,
        public string $sourceType,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
        public ?\DateTimeImmutable $publishedAt,
        public ?\DateTimeImmutable $expiresAt,
        public array $metadata,
    ) {
        $this->validate();
    }

    public static function create(
        VerticalSource $source,
        ListingType $type,
        string $title,
        string $description,
        Money $price,
        int $tenantId,
        int $businessGroupId,
        int $sourceId,
        string $sourceType,
        array $categories = [],
        array $tags = [],
        array $images = [],
        array $attributes = [],
        array $metadata = [],
        ?string $promotionType = null,
        ?float $promotionBudget = null,
        ?\DateTimeImmutable $promotionStartAt = null,
        ?\DateTimeImmutable $promotionEndAt = null,
        int $promotionPriorityBoost = 0,
        int $priorityBoost = 0,
    ): self {
        return new self(
            uuid: \Ramsey\Uuid\Uuid::uuid4(),
            source: $source,
            type: $type,
            status: ListingStatus::DRAFT,
            title: $title,
            description: $description,
            price: $price,
            rating: null,
            reviewCount: 0,
            viewCount: 0,
            orderCount: 0,
            conversionRate: 0.0,
            popularityScore: 0.0,
            rankingScore: 0.0,
            categories: $categories,
            tags: $tags,
            images: $images,
            thumbnail: $images[0] ?? null,
            attributes: $attributes,
            stockQuantity: 0,
            inStock: false,
            isFeatured: false,
            isPromoted: $promotionType !== null,
            promotionType: $promotionType,
            promotionBudget: $promotionBudget,
            promotionStartAt: $promotionStartAt,
            promotionEndAt: $promotionEndAt,
            promotionImpressions: 0,
            promotionClicks: 0,
            promotionSpendKopecks: 0,
            promotionPriorityBoost: $promotionPriorityBoost,
            priorityBoost: $priorityBoost,
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            sourceId: $sourceId,
            sourceType: $sourceType,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            publishedAt: null,
            expiresAt: null,
            metadata: $metadata,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            uuid: \Ramsey\Uuid\Uuid::fromString($data['uuid']),
            source: VerticalSource::from($data['source']),
            type: ListingType::from($data['type']),
            status: ListingStatus::from($data['status']),
            title: $data['title'],
            description: $data['description'],
            price: Money::fromArray($data['price']),
            rating: isset($data['rating']) ? Rating::fromArray($data['rating']) : null,
            reviewCount: (int) $data['review_count'],
            viewCount: (int) $data['view_count'],
            orderCount: (int) $data['order_count'],
            conversionRate: (float) $data['conversion_rate'],
            popularityScore: (float) $data['popularity_score'],
            rankingScore: (float) $data['ranking_score'],
            categories: (array) $data['categories'],
            tags: (array) $data['tags'],
            images: (array) $data['images'],
            thumbnail: $data['thumbnail'] ?? null,
            attributes: (array) $data['attributes'],
            stockQuantity: (int) $data['stock_quantity'],
            inStock: (bool) $data['in_stock'],
            isFeatured: (bool) $data['is_featured'],
            isPromoted: (bool) $data['is_promoted'],
            promotionType: $data['promotion_type'] ?? null,
            promotionBudget: isset($data['promotion_budget']) ? (float) $data['promotion_budget'] : null,
            promotionStartAt: isset($data['promotion_start_at']) ? new \DateTimeImmutable($data['promotion_start_at']) : null,
            promotionEndAt: isset($data['promotion_end_at']) ? new \DateTimeImmutable($data['promotion_end_at']) : null,
            promotionImpressions: (int) ($data['promotion_impressions'] ?? 0),
            promotionClicks: (int) ($data['promotion_clicks'] ?? 0),
            promotionSpendKopecks: (int) ($data['promotion_spend_kopecks'] ?? 0),
            promotionPriorityBoost: (int) ($data['promotion_priority_boost'] ?? 0),
            priorityBoost: (int) ($data['priority_boost'] ?? 0),
            tenantId: (int) $data['tenant_id'],
            businessGroupId: (int) $data['business_group_id'],
            sourceId: (int) $data['source_id'],
            sourceType: $data['source_type'],
            createdAt: new \DateTimeImmutable($data['created_at']),
            updatedAt: new \DateTimeImmutable($data['updated_at']),
            publishedAt: isset($data['published_at']) ? new \DateTimeImmutable($data['published_at']) : null,
            expiresAt: isset($data['expires_at']) ? new \DateTimeImmutable($data['expires_at']) : null,
            metadata: (array) $data['metadata'],
        );
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid->toString(),
            'source' => $this->source->value,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price->toArray(),
            'rating' => $this->rating?->toArray(),
            'review_count' => $this->reviewCount,
            'view_count' => $this->viewCount,
            'order_count' => $this->orderCount,
            'conversion_rate' => $this->conversionRate,
            'popularity_score' => $this->popularityScore,
            'ranking_score' => $this->rankingScore,
            'categories' => $this->categories,
            'tags' => $this->tags,
            'images' => $this->images,
            'thumbnail' => $this->thumbnail,
            'attributes' => $this->attributes,
            'stock_quantity' => $this->stockQuantity,
            'in_stock' => $this->inStock,
            'is_featured' => $this->isFeatured,
            'is_promoted' => $this->isPromoted,
            'promotion_type' => $this->promotionType,
            'promotion_budget' => $this->promotionBudget,
            'promotion_start_at' => $this->promotionStartAt?->format('Y-m-d H:i:s'),
            'promotion_end_at' => $this->promotionEndAt?->format('Y-m-d H:i:s'),
            'promotion_impressions' => $this->promotionImpressions,
            'promotion_clicks' => $this->promotionClicks,
            'promotion_spend_kopecks' => $this->promotionSpendKopecks,
            'promotion_priority_boost' => $this->promotionPriorityBoost,
            'priority_boost' => $this->priorityBoost,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'source_id' => $this->sourceId,
            'source_type' => $this->sourceType,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'published_at' => $this->publishedAt?->format('Y-m-d H:i:s'),
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
        ];
    }

    public function withStatus(ListingStatus $status): self
    {
        return new self(
            ...$this->toArray(),
            status: $status,
            publishedAt: $status === ListingStatus::PUBLISHED ? new \DateTimeImmutable() : null,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withRankingScore(float $score): self
    {
        return new self(
            ...$this->toArray(),
            rankingScore: $score,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withPopularityScore(float $score): self
    {
        return new self(
            ...$this->toArray(),
            popularityScore: $score,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withMetrics(int $viewCount, int $orderCount, float $conversionRate): self
    {
        return new self(
            ...$this->toArray(),
            viewCount: $this->viewCount + $viewCount,
            orderCount: $this->orderCount + $orderCount,
            conversionRate: $conversionRate,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withRating(Rating $rating, int $reviewCount): self
    {
        return new self(
            ...$this->toArray(),
            rating: $rating,
            reviewCount: $reviewCount,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withStock(int $quantity, bool $inStock): self
    {
        return new self(
            ...$this->toArray(),
            stockQuantity: $quantity,
            inStock: $inStock,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withFeatured(bool $isFeatured): self
    {
        return new self(
            ...$this->toArray(),
            isFeatured: $isFeatured,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withPromoted(bool $isPromoted): self
    {
        return new self(
            ...$this->toArray(),
            isPromoted: $isPromoted,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function isPublished(): bool
    {
        return $this->status === ListingStatus::PUBLISHED;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable();
    }

    public function isAvailable(): bool
    {
        return $this->isPublished() && $this->inStock && !$this->isExpired();
    }

    public function getPrimaryCategory(): ?string
    {
        return $this->categories[0] ?? null;
    }

    public function hasCategory(string $category): bool
    {
        return in_array($category, $this->categories, true);
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Проверить, активна ли реклама для позиции
     */
    public function isPromotionActive(): bool
    {
        if (!$this->isPromoted || $this->promotionType === null) {
            return false;
        }

        $now = new \DateTimeImmutable();

        if ($this->promotionStartAt !== null && $now < $this->promotionStartAt) {
            return false;
        }

        if ($this->promotionEndAt !== null && $now > $this->promotionEndAt) {
            return false;
        }

        return true;
    }

    /**
     * Получить приоритет с учетом рекламы
     */
    public function getEffectivePriority(): int
    {
        return $this->priorityBoost + ($this->isPromotionActive() ? 100 : 0);
    }

    /**
     * Получить тип рекламы
     */
    public function getPromotionType(): ?string
    {
        return $this->promotionType;
    }

    /**
     * Получить бюджет рекламы
     */
    public function getPromotionBudget(): ?float
    {
        return $this->promotionBudget;
    }

    /**
     * Проверить, может ли позиция быть показана на титул (главной странице)
     */
    public function canShowOnHomepage(): bool
    {
        return $this->isAvailable()
            && $this->isPublished()
            && ($this->isFeatured || $this->isPromotionActive());
    }

    /**
     * Получить позицию в рекламной очереди
     */
    public function getAdPosition(): int
    {
        return $this->promotionPriorityBoost + $this->priorityBoost;
    }

    /**
     * Получить количество показов рекламы
     */
    public function getPromotionImpressions(): int
    {
        return $this->promotionImpressions;
    }

    /**
     * Получить количество кликов по рекламе
     */
    public function getPromotionClicks(): int
    {
        return $this->promotionClicks;
    }

    /**
     * Получить потраченный бюджет на рекламу (в копейках)
     */
    public function getPromotionSpendKopecks(): int
    {
        return $this->promotionSpendKopecks;
    }

    /**
     * Получить CTR рекламы (click-through rate)
     */
    public function getPromotionCTR(): float
    {
        if ($this->promotionImpressions === 0) {
            return 0.0;
        }

        return $this->promotionClicks / $this->promotionImpressions;
    }

    /**
     * Проверить, превышен ли бюджет рекламы
     */
    public function isPromotionBudgetExceeded(): bool
    {
        if ($this->promotionBudget === null) {
            return false;
        }

        return $this->promotionSpendKopecks >= ($this->promotionBudget * 100);
    }

    /**
     * Записать показ рекламы
     */
    public function recordImpression(int $costKopecks): self
    {
        return new self(
            ...$this->toArray(),
            promotionImpressions: $this->promotionImpressions + 1,
            promotionSpendKopecks: $this->promotionSpendKopecks + $costKopecks,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Записать клик по рекламе
     */
    public function recordClick(int $costKopecks): self
    {
        return new self(
            ...$this->toArray(),
            promotionClicks: $this->promotionClicks + 1,
            promotionSpendKopecks: $this->promotionSpendKopecks + $costKopecks,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    private function validate(): void
    {
        if (empty($this->title)) {
            throw new \InvalidArgumentException('Title cannot be empty');
        }

        if (mb_strlen($this->title) > 255) {
            throw new \InvalidArgumentException('Title cannot exceed 255 characters');
        }

        if (mb_strlen($this->description) > 5000) {
            throw new \InvalidArgumentException('Description cannot exceed 5000 characters');
        }

        if ($this->conversionRate < 0 || $this->conversionRate > 1) {
            throw new \InvalidArgumentException('Conversion rate must be between 0 and 1');
        }

        if ($this->popularityScore < 0) {
            throw new \InvalidArgumentException('Popularity score cannot be negative');
        }

        if ($this->rankingScore < 0) {
            throw new \InvalidArgumentException('Ranking score cannot be negative');
        }

        if ($this->stockQuantity < 0) {
            throw new \InvalidArgumentException('Stock quantity cannot be negative');
        }

        if ($this->reviewCount < 0) {
            throw new \InvalidArgumentException('Review count cannot be negative');
        }

        if ($this->viewCount < 0) {
            throw new \InvalidArgumentException('View count cannot be negative');
        }

        if ($this->orderCount < 0) {
            throw new \InvalidArgumentException('Order count cannot be negative');
        }
    }
}
