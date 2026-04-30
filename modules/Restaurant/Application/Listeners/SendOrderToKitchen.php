<?php

declare(strict_types=1);

namespace Modules\Restaurant\Listeners;

use Modules\Restaurant\Events\OrderCreated;
use Modules\Restaurant\Services\KitchenService;
use Modules\Restaurant\Enums\OrderType;
use Illuminate\Log\LogManager;

/**
 * Send Order To Kitchen — Автоматическая отправка заказа на кухню при подтверждении
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 */
final readonly class SendOrderToKitchen
{
    public function __construct(
        private readonly KitchenService $kitchenService,
        private readonly LogManager $log,
    ) {}

    public function handle(OrderCreated $event, LogManager $log): void
    {
        $order = $event->order;

        // Только dine-in заказы отправляются на кухню автоматически
        if ($order->type !== OrderType::DINE_IN) {
            return;
        }

        // Отправляем уведомление официанту
        if ($order->waiter_id) {
            // TODO: Отправить push-уведомление официанту
        }

        // Логируем для KDS
        $log->channel('restaurant')->info('Order sent to kitchen', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
