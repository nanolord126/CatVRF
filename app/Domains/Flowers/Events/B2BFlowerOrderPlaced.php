<?php declare(strict_types=1);

namespace App\Domains\Flowers\Events;

use App\Domains\Flowers\Models\B2BFlowerOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class B2BFlowerOrderPlaced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly B2BFlowerOrder $order,
        public readonly string $correlationId,
    ) {}
}
