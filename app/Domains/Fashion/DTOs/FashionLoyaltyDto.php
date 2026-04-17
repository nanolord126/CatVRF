<?php declare(strict_types=1);

namespace App\Domains\Fashion\DTOs;

final readonly class FashionLoyaltyDto
{
    public function __construct(
        public int $userId,
        public int $tenantId,
        public int $currentPoints,
        public int $pointsEarned,
        public string $tier,
        public bool $nftUnlocked,
        public ?string $nftAvatarUrl,
        public ?array $nftMetadata,
        public string $correlationId,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'current_points' => $this->currentPoints,
            'points_earned' => $this->pointsEarned,
            'tier' => $this->tier,
            'nft_unlocked' => $this->nftUnlocked,
            'nft_avatar_url' => $this->nftAvatarUrl,
            'nft_metadata' => $this->nftMetadata,
            'correlation_id' => $this->correlationId,
        ];
    }
}
