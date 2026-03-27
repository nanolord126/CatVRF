<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Jobs;

use App\Services\InventoryManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final /**
 * LowStockNotificationJob
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class LowStockNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $correlationId = '',
    ) {}

    public function handle(InventoryManagementService $inventory): void
    {
        $lowStockItems = $inventory->checkLowStock();

        foreach ($lowStockItems as $item) {
            Log::channel('audit')->warning('Low stock alert', [
                'item_id' => $item->id,
                'current_stock' => $item->current_stock,
                'threshold' => $item->min_stock_threshold,
                'correlation_id' => $this->correlationId,
            ]);
        }
    }
}
