<?php declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LowStockNotificationJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
