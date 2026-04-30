<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Listeners;

use Modules\Flowers\Domain\Events\OrderCreated;
use Modules\Flowers\Domain\Events\OrderStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Flowers\Domain\Enums\OrderStatus;

final class NotifyFloristOfNewOrder implements ShouldQueue
{
    public function handle(OrderCreated|OrderStatusChanged $event): void
    {
        $order = $event->order;

        if ($order->floristId === null) {
            return;
        }

        Log::info('Notifying florist', [
            'order_id' => $order->id,
            'florist_id' => $order->floristId,
            'status' => $order->status->value,
        ]);

        // Send notification to florist
        // Could use push notification, email, or in-app notification
    }
}
