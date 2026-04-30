<?php

declare(strict_types=1);

namespace Modules\Recommendation\Application\DTOs;

final readonly class RecommendationResponseDTO
{
    /**
     * @param array<int, array{item_id: int, seller_id: int, score: float, confidence: float, source: string, position: int, vertical: string, reason: string|null}> $items
     */
    public function __construct(
        public int $tenantId,
        public int $userId,
        public string $scenario,
        public array $items,
        public string $correlationId,
        public float $latencyMs,
        public string $modelVersion,
        public bool $servedFromCache = false,
        public ?string $fallbackReason = null,
    ) {}

    public function itemCount(): int
    {
        return count($this->items);
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'scenario' => $this->scenario,
            'items' => $this->items,
            'correlation_id' => $this->correlationId,
            'latency_ms' => round($this->latencyMs, 2),
            'model_version' => $this->modelVersion,
            'served_from_cache' => $this->servedFromCache,
            'fallback_reason' => $this->fallbackReason,
            'item_count' => $this->itemCount(),
        ];
    }
}
