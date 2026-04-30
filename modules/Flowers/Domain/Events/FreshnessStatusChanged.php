<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Events;

use Modules\Flowers\Domain\Entities\Flower;
use Modules\Flowers\Domain\Enums\FreshnessStatus;
use Illuminate\Foundation\Events\Dispatchable;

final class FreshnessStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Flower $flower,
        public readonly FreshnessStatus $previousStatus,
        public readonly FreshnessStatus $newStatus,
    ) {}
}
