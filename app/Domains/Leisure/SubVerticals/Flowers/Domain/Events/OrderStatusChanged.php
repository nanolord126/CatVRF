<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Events;

use Modules\Flowers\Domain\Entities\Order;
use Modules\Flowers\Domain\Enums\OrderStatus;
use Illuminate\Foundation\Events\Dispatchable;

final class OrderStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Order $order,
        public readonly OrderStatus $previousStatus,
    ) {}
}
