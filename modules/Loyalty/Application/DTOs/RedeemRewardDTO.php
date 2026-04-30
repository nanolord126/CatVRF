<?php

declare(strict_types=1);

namespace Modules\Loyalty\Application\DTOs;

use Modules\Loyalty\Domain\ValueObjects\Points;

final readonly class RedeemRewardDTO
{
    private function __construct(
        public string $profileId,
        public string $rewardId,
        public Points $pointsCost,
        public ?string $orderUuid,
        public ?array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            profileId: $data['profile_id'],
            rewardId: $data['reward_id'],
            pointsCost: Points::fromFloat((float) $data['points_cost']),
            orderUuid: $data['order_uuid'] ?? null,
            metadata: $data['metadata'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'profile_id' => $this->profileId,
            'reward_id' => $this->rewardId,
            'points_cost' => $this->pointsCost->getValue(),
            'order_uuid' => $this->orderUuid,
            'metadata' => $this->metadata,
        ];
    }
}
