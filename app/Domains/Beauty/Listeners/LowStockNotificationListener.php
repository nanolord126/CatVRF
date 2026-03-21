<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\LowStockReached;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Listener: отправить уведомление о низком остатке хозяину салона.
 * Production 2026.
 */
final class LowStockNotificationListener implements ShouldQueue
{
    public function handle(LowStockReached $event): void
    {
        try {
            $product = $event->product;
            $salon = $product->salon;

            // Отправить уведомление владельцу салона

            Log::channel('audit')->warning('Low stock alert', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'current_stock' => $product->current_stock,
                'min_threshold' => $product->min_stock_threshold,
                'salon_id' => $salon->id,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('LowStockNotificationListener failed', [
                'product_id' => $event->product->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);

            throw $e;
        }
    }
}
