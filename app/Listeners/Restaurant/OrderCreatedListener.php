<?php

declare(strict_types=1);

namespace App\Listeners\Restaurant;

use App\Events\OrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Restaurant\Application\Services\MarketplaceOrderIntegrationService;

final class OrderCreatedListener implements ShouldQueue
{
    public function __construct(
        private readonly MarketplaceOrderIntegrationService $marketplaceIntegration,
    ) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        // Обрабатываем только заказы ресторанной вертикали
        if (!in_array($order->vertical, ['restaurant', 'food'], true)) {
            return;
        }

        // Отправляем заказ на кухню через KDS
        $this->marketplaceIntegration->processMarketplaceOrder($order);
    }
}
