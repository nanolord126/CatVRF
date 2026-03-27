<?php

declare(strict_types=1);

namespace App\Domains\Food\Beverages\Events;

use App\Domains\Food\Beverages\Models\BeverageOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class BeverageOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly BeverageOrder $order,
        public readonly string $correlationId,
    ) {}
}
