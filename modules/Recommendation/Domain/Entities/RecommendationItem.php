<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Entities;

use Modules\Recommendation\Domain\Enums\RecommendationScenario;
use Modules\Recommendation\Domain\Enums\RecommendationSource;
use Modules\Recommendation\Domain\ValueObjects\RecommendationScore;

final class RecommendationItem
{
    private ?\DateTimeImmutable $impressedAt = null;
    private ?\DateTimeImmutable $clickedAt = null;
    private ?\DateTimeImmutable $convertedAt = null;

    public function __construct(
        private ?int $id,
        private readonly int $tenantId,
        private readonly int $userId,
        private readonly int $itemId,
        private readonly int $sellerId,
        private readonly string $vertical,
        private readonly RecommendationScore $score,
        private readonly RecommendationSource $source,
        private readonly RecommendationScenario $scenario,
        private readonly string $correlationId,
        private readonly int $position = 0,
        private readonly ?string $modelVersion = null,
        private readonly array $context = [],
    ) {}

    public static function fromInference(
        int $tenantId,
        int $userId,
        int $itemId,
        int $sellerId,
        string $vertical,
        float $scoreValue,
        float $confidence,
        RecommendationSource $source,
        RecommendationScenario $scenario,
        string $correlationId,
        int $position = 0,
        ?string $modelVersion = null,
        array $context = [],
    ): self {
        return new self(
            id: null,
            tenantId: $tenantId,
            userId: $userId,
            itemId: $itemId,
            sellerId: $sellerId,
            vertical: $vertical,
            score: RecommendationScore::fromRaw($scoreValue, $confidence),
            source: $source,
            scenario: $scenario,
            correlationId: $correlationId,
            position: $position,
            modelVersion: $modelVersion,
            context: $context,
        );
    }

    public function recordImpression(): void
    {
        $this->impressedAt = new \DateTimeImmutable();
    }

    public function recordClick(): void
    {
        $this->clickedAt = new \DateTimeImmutable();
    }

    public function recordConversion(): void
    {
        $this->convertedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getUserId(): int { return $this->userId; }
    public function getItemId(): int { return $this->itemId; }
    public function getSellerId(): int { return $this->sellerId; }
    public function getVertical(): string { return $this->vertical; }
    public function getScore(): RecommendationScore { return $this->score; }
    public function getSource(): RecommendationSource { return $this->source; }
    public function getScenario(): RecommendationScenario { return $this->scenario; }
    public function getCorrelationId(): string { return $this->correlationId; }
    public function getPosition(): int { return $this->position; }
    public function getModelVersion(): ?string { return $this->modelVersion; }
    public function getContext(): array { return $this->context; }
    public function getImpressedAt(): ?\DateTimeImmutable { return $this->impressedAt; }
    public function getClickedAt(): ?\DateTimeImmutable { return $this->clickedAt; }
    public function getConvertedAt(): ?\DateTimeImmutable { return $this->convertedAt; }

    public function wasClicked(): bool { return $this->clickedAt !== null; }
    public function wasConverted(): bool { return $this->convertedAt !== null; }

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
            'position' => $this->position,
            'model_version' => $this->modelVersion,
            'correlation_id' => $this->correlationId,
            'impressed_at' => $this->impressedAt?->format('c'),
            'clicked_at' => $this->clickedAt?->format('c'),
            'converted_at' => $this->convertedAt?->format('c'),
        ];
    }
}
