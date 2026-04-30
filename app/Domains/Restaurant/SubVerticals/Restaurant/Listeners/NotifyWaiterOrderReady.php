<?php

declare(strict_types=1);

namespace Modules\Restaurant\Listeners;

use Modules\Restaurant\Events\KitchenOrderReady;
use Illuminate\Log\LogManager;

/**
 * Notify Waiter Order Ready — Уведомление официанта о готовности заказа
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 */
final readonly class NotifyWaiterOrderReady
{
    public function handle(KitchenOrderReady $event, LogManager $log): void
    {
        $order = $event->order;

        if (!$order->waiter_id) {
            return;
        }

        // TODO: Отправить push-уведомление официанту
        // TODO: Отправить уведомление в Telegram/VK если настроено

        $log->channel('restaurant')->info('Waiter notified: order ready', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'waiter_id' => $order->waiter_id,
            'table_id' => $order->table_id,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
