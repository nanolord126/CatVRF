<?php declare(strict_types=1);

namespace Modules\Fashion\Events;

use App\Domains\Fashion\Models\FashionOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FashionOrderPlaced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FashionOrder $order,
        public readonly string $correlationId
    ) {}
}
