<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

final readonly class LoyaltyProfile
{
    public function __construct(
        public int $id,
        public int $clientId,
        public int $venueId,
        public int $pointsBalance,
        public int $pointsEarned,
        public int $pointsRedeemed,
        public string $tier,
        public float $totalSpent,
        public int $totalVisits,
        public ?\DateTimeImmutable $tierUpdatedAt,
        public ?\DateTimeImmutable $lastActivityAt,
        public ?int $birthdayGiftSentYear,
        public ?array $preferences,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            clientId: $data['client_id'],
            venueId: $data['venue_id'],
            pointsBalance: (int) $data['points_balance'],
            pointsEarned: (int) $data['points_earned'],
            pointsRedeemed: (int) $data['points_redeemed'],
            tier: $data['tier'],
            totalSpent: (float) $data['total_spent'],
            totalVisits: (int) $data['total_visits'],
            tierUpdatedAt: $data['tier_updated_at'] ? new \DateTimeImmutable($data['tier_updated_at']) : null,
            lastActivityAt: $data['last_activity_at'] ? new \DateTimeImmutable($data['last_activity_at']) : null,
            birthdayGiftSentYear: $data['birthday_gift_sent_year'] ?? null,
            preferences: $data['preferences'] ?? null,
            createdAt: new \DateTimeImmutable($data['created_at']),
            updatedAt: $data['updated_at'] ? new \DateTimeImmutable($data['updated_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->clientId,
            'venue_id' => $this->venueId,
            'points_balance' => $this->pointsBalance,
            'points_earned' => $this->pointsEarned,
            'points_redeemed' => $this->pointsRedeemed,
            'tier' => $this->tier,
            'total_spent' => $this->totalSpent,
            'total_visits' => $this->totalVisits,
            'tier_updated_at' => $this->tierUpdatedAt?->format('Y-m-d H:i:s'),
            'last_activity_at' => $this->lastActivityAt?->format('Y-m-d H:i:s'),
            'birthday_gift_sent_year' => $this->birthdayGiftSentYear,
            'preferences' => $this->preferences,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    public function canRedeemPoints(int $points): bool
    {
        return $this->pointsBalance >= $points;
    }

    public function getTierOrder(): int
    {
        return match ($this->tier) {
            'bronze' => 1,
            'silver' => 2,
            'gold' => 3,
            'platinum' => 4,
            default => 0,
        };
    }

    public function shouldUpgradeTier(string $newTier): bool
    {
        return $this->getTierOrder() < (new self(...get_object_vars($this), tier: $newTier))->getTierOrder();
    }
}
