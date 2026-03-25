declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Food\Listeners;

use App\Domains\Food\Events\LowConsumableStock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener для уведомления о низком остатке ингредиентов.
 * Production 2026.
 */
final class LowConsumableStockAlertListener implements ShouldQueue
{
    public function handle(LowConsumableStock $event): void
    {
        try {
            $this->log->channel('audit')->warning('Low consumable stock alert', [
                'consumable_id' => $event->consumable->id,
                'name' => $event->consumable->name,
                'current_stock' => $event->consumable->current_stock,
                'min_threshold' => $event->consumable->min_stock_threshold,
                'unit' => $event->consumable->unit,
                'correlation_id' => $event->correlationId,
            ]);
            // Notification::send($event->consumable->restaurant->owner, new LowStockNotification($event->consumable));
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Low stock alert failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);

            throw $e;
        }
    }
}
