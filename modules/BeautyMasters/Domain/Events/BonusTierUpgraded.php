<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class BonusTierUpgraded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $userId,
        public int $venueId,
        public string $oldTier,
        public string $newTier,
    ) {
    }
}
