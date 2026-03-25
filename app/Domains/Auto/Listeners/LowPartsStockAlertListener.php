declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use App\Domains\Auto\Events\LowPartsStock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener: отправить алерт о низком остатке запчастей.
 * Production 2026.
 */
final class LowPartsStockAlertListener implements ShouldQueue
{
    public function handle(LowPartsStock $event): void
    {
        try {
            $part = $event->part;

            $this->log->channel('audit')->warning('Low auto parts stock alert', [
                'part_id' => $part->id,
                'part_name' => $part->name,
                'current_stock' => $part->current_stock,
                'min_threshold' => $part->min_stock_threshold,
                'sku' => $part->sku,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('LowPartsStockAlertListener failed', [
                'part_id' => $event->part->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);

            throw $e;
        }
    }
}
