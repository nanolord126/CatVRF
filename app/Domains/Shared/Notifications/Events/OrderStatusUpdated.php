<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Events;

use App\Domains\Supermarket\Models\SupermarketOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class OrderStatusUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public SupermarketOrder $order,
        public string $oldStatus,
        public string $newStatus
    ) {}
}
