<?php

declare(strict_types=1);

namespace Modules\Restaurant\Listeners;

use Modules\Restaurant\Events\OrderStatusChanged;
use Modules\Restaurant\Services\LoyaltyService;
use Modules\Restaurant\Enums\OrderStatus;

/**
 * Award Loyalty Bonus — Автоматическое начисление бонусов при завершении заказа
 */
final class AwardLoyaltyBonus
{
    public function __construct(
        private readonly LoyaltyService $loyaltyService
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        // Начисляем бонусы только при завершении заказа
        if ($event->newStatus !== OrderStatus::COMPLETED) {
            return;
        }

        $order = $event->order;

        if (!$order->guest_id) {
            return;
        }

        try {
            $this->loyaltyService->earnBonusFromOrder($order);
        } catch (\Exception $e) {
            \Log::channel('restaurant')->error('Failed to award loyalty bonus', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
        }
    }
}
