<?php declare(strict_types=1);

namespace App\Jobs\Inventory;

use App\Services\InventoryManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class LowStockAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('notifications');
    }

    public function tags(): array
    {
        return ['inventory', 'stock-alert', 'multi-vertical'];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }

    public function handle(InventoryManagementService $inventoryService): void
    {
        try {
            DB::transaction(function () use ($inventoryService) {
                $lowStockItems = $inventoryService->checkLowStock();

                if ($lowStockItems->isEmpty()) {
                    Log::channel('audit')->info('Low stock check completed - no items below threshold', [
                        'correlation_id' => $this->correlationId,
                    ]);

                    return;
                }

                $lowStockItems->each(function ($item) {
                    Log::channel('audit')->warning('Item below minimum stock threshold', [
                        'correlation_id' => $this->correlationId,
                        'inventory_item_id' => $item->id,
                        'tenant_id' => $item->tenant_id,
                        'current_stock' => $item->current_stock,
                        'min_threshold' => $item->min_stock_threshold,
                    ]);
                    // NotificationService::alertLowStock($item, $this->correlationId);
                });
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Low stock alert job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
