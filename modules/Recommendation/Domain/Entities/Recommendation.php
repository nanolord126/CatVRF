<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Entities;

use Modules\Recommendation\Domain\Enums\RecommendationSource;
use Modules\Recommendation\Domain\Enums\RecommendationScenario;
use Modules\Recommendation\Domain\ValueObjects\RecommendationScore;

final class Recommendation
{
    private function __construct(
        private readonly int $id,
        private readonly int $tenantId,
        private readonly int $userId,
        private readonly int $itemId,
        private readonly int $sellerId,
        private readonly string $vertical,
        private readonly RecommendationScore $score,
        private readonly RecommendationSource $source,
        private readonly RecommendationScenario $scenario,
        private readonly string $correlationId,
        private readonly int $position,
        private readonly ?string $modelVersion,
        private readonly array $context,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            tenantId: $data['tenant_id'],
            userId: $data['user_id'],
            itemId: $data['item_id'],
            sellerId: $data['seller_id'],
            vertical: $data['vertical'],
            score: RecommendationScore::fromRaw($data['score']['value'] ?? 0.0, $data['score']['confidence'] ?? 0.0),
            source: RecommendationSource::from($data['source']),
            scenario: RecommendationScenario::from($data['scenario']),
            correlationId: $data['correlation_id'],
            position: $data['position'] ?? 0,
            modelVersion: $data['model_version'] ?? null,
            context: $data['context'] ?? [],
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function getSellerId(): int
    {
        return $this->sellerId;
    }

    public function getVertical(): string
    {
        return $this->vertical;
    }

    public function getScore(): RecommendationScore
    {
        return $this->score;
    }

    public function getSource(): RecommendationSource
    {
        return $this->source;
    }

    public function getScenario(): RecommendationScenario
    {
        return $this->scenario;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getModelVersion(): ?string
    {
        return $this->modelVersion;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'item_id' => $this->itemId,
            'seller_id' => $this->sellerId,
            'vertical' => $this->vertical,
            'score' => $this->score->toArray(),
            'source' => $this->source->value,
            'scenario' => $this->scenario->value,
            'correlation_id' => $this->correlationId,
            'position' => $this->position,
            'model_version' => $this->modelVersion,
            'context' => $this->context,
        ];
    }
}
