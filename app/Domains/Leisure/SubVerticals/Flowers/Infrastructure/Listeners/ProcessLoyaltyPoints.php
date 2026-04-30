<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Listeners;

use Modules\Flowers\Domain\Events\OrderStatusChanged;
use Modules\Flowers\Domain\Enums\OrderStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class ProcessLoyaltyPoints implements ShouldQueue
{
    public function handle(OrderStatusChanged $event): void
    {
        if ($event->order->status !== OrderStatus::DELIVERED && $event->order->status !== OrderStatus::PICKED_UP) {
            return;
        }

        $pointsEarned = (int) floor($event->order->totalAmount / 100); // 1 point per 100 RUB

        Log::info('Processing loyalty points', [
            'order_id' => $event->order->id,
            'order_number' => $event->order->orderNumber,
            'points_earned' => $pointsEarned,
            'total_amount' => $event->order->totalAmount,
        ]);

        // Update order with loyalty points earned
        // This would be handled in the OrderService's markAsDelivered method
    }
}
