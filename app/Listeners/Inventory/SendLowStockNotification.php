<?php

declare(strict_types=1);

namespace App\Listeners\Inventory;

use App\Events\Inventory\StockLowAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Send Low Stock Notification Listener
 *
 * Sends notifications when stock falls below threshold.
 * Integrates with notification system for alerts.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class SendLowStockNotification
{
    public function handle(StockLowAlert $event): void
    {
        Log::warning('Low stock alert', [
            'item_id' => $event->itemId,
            'sku' => $event->sku,
            'item_name' => $event->itemName,
            'current_stock' => $event->currentStock,
            'min_threshold' => $event->minStockThreshold,
            'tenant_id' => $event->tenantId,
            'correlation_id' => $event->correlationId,
        ]);

        // TODO: Implement actual notification sending
        // This would integrate with the existing notification system
        // Example: Notification::send($admins, new LowStockNotification($event));
    }
}
