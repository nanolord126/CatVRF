<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Events;

use Modules\Flowers\Domain\Entities\Flower;
use Illuminate\Foundation\Events\Dispatchable;

final class LowStockAlert
{
    use Dispatchable;

    public function __construct(
        public readonly Flower $flower,
    ) {}
}
