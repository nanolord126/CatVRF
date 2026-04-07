<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\ValueObjects;

final readonly class LoyaltyStatus
{
    public function __construct(
        public string $level,
        public int $points,
        public float $discountPercentage
    ) {
    }
}
