<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Entities;

final class RecommendationClick
{
    private function __construct(
        private readonly int $sellerId,
        private readonly int $productId,
        private readonly int $userId,
        private readonly string $deviceFingerprint,
        private readonly \DateTimeImmutable $clickedAt,
        private readonly bool $converted,
    ) {}

    public static function create(array $data): self
    {
        return new self(
            sellerId: $data['seller_id'],
            productId: $data['product_id'],
            userId: $data['user_id'],
            deviceFingerprint: $data['device_fingerprint'],
            clickedAt: $data['clicked_at'] instanceof \DateTimeImmutable 
                ? $data['clicked_at'] 
                : new \DateTimeImmutable($data['clicked_at']),
            converted: $data['converted'] ?? false,
        );
    }

    public function getSellerId(): int
    {
        return $this->sellerId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getDeviceFingerprint(): string
    {
        return $this->deviceFingerprint;
    }

    public function getClickedAt(): \DateTimeImmutable
    {
        return $this->clickedAt;
    }

    public function isConverted(): bool
    {
        return $this->converted;
    }

    public function toArray(): array
    {
        return [
            'seller_id' => $this->sellerId,
            'product_id' => $this->productId,
            'user_id' => $this->userId,
            'device_fingerprint' => $this->deviceFingerprint,
            'clicked_at' => $this->clickedAt->format('c'),
            'converted' => $this->converted,
        ];
    }
}
