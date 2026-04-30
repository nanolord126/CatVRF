<?php

declare(strict_types=1);

namespace App\Listeners\Restaurant;

use App\Events\OrderStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Restaurant\Application\Services\MarketplaceOrderIntegrationService;

final class OrderStatusChangedListener implements ShouldQueue
{
    public function __construct(
        private readonly MarketplaceOrderIntegrationService $marketplaceIntegration,
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;

        // Синхронизируем статус с кухней
        $this->marketplaceIntegration->syncOrderStatus($order);
    }
}
