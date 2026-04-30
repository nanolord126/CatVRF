<?php

declare(strict_types=1);

namespace Modules\Recommendation\Application\DTOs;

use Modules\Recommendation\Domain\Enums\RecommendationScenario;
use Modules\Recommendation\Domain\Enums\RecommendationSource;

final readonly class RecommendationRequestDTO
{
    public function __construct(
        public int $tenantId,
        public int $userId,
        public RecommendationScenario $scenario,
        public ?int $itemId = null,
        public ?int $sellerId = null,
        public ?string $query = null,
        public ?string $vertical = null,
        public int $limit = 20,
        public array $context = [],
        public ?string $correlationId = null,
        public ?RecommendationSource $preferredSource = null,
    ) {}

    public static function forHomeFeed(int $tenantId, int $userId, array $context = [], ?string $correlationId = null): self
    {
        return new self(
            tenantId: $tenantId,
            userId: $userId,
            scenario: RecommendationScenario::HOME_FEED,
            context: $context,
            correlationId: $correlationId,
        );
    }

    public static function forProductDetail(int $tenantId, int $userId, int $itemId, array $context = [], ?string $correlationId = null): self
    {
        return new self(
            tenantId: $tenantId,
            userId: $userId,
            scenario: RecommendationScenario::PRODUCT_DETAIL,
            itemId: $itemId,
            context: $context,
            correlationId: $correlationId,
        );
    }

    public static function forSearch(int $tenantId, int $userId, string $query, array $context = [], ?string $correlationId = null): self
    {
        return new self(
            tenantId: $tenantId,
            userId: $userId,
            scenario: RecommendationScenario::SEARCH,
            query: $query,
            context: $context,
            correlationId: $correlationId,
        );
    }

    public static function forSellerPage(int $tenantId, int $userId, int $sellerId, array $context = [], ?string $correlationId = null): self
    {
        return new self(
            tenantId: $tenantId,
            userId: $userId,
            scenario: RecommendationScenario::SELLER_PAGE,
            sellerId: $sellerId,
            context: $context,
            correlationId: $correlationId,
        );
    }

    public static function forCart(int $tenantId, int $userId, array $context = [], ?string $correlationId = null): self
    {
        return new self(
            tenantId: $tenantId,
            userId: $userId,
            scenario: RecommendationScenario::CART,
            limit: 6,
            context: $context,
            correlationId: $correlationId,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'scenario' => $this->scenario->value,
            'item_id' => $this->itemId,
            'seller_id' => $this->sellerId,
            'query' => $this->query,
            'vertical' => $this->vertical,
            'limit' => $this->limit,
            'correlation_id' => $this->correlationId,
        ];
    }
}
