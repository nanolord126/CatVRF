<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Events;

use Modules\Flowers\Domain\Entities\Order;
use Illuminate\Foundation\Events\Dispatchable;

final class OrderCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Order $order,
    ) {}
}
