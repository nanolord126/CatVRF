<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use App\Services\InventoryManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * LowStockNotificationJob — проверяет остатки всех расходников и отправляет
 * уведомление владельцу при падении ниже минимального порога.
 *
 * Запускается ежедневно в 08:00.
 */
final class LowStockNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    private string $correlationId;

    public function __construct(string $correlationId = '')
    {
        $this->correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();
    }

    public function handle(
        InventoryManagementService $inventory,
        LoggerInterface            $logger,
    ): void {
        $lowStockItems = $inventory->checkLowStock();

        if ($lowStockItems->isEmpty()) {
            return;
        }

        foreach ($lowStockItems as $item) {
            $logger->warning('Low stock alert for Beauty consumable.', [
                'item_id'         => $item->id,
                'current_stock'   => $item->current_stock,
                'min_threshold'   => $item->min_stock_threshold,
                'correlation_id'  => $this->correlationId,
            ]);
        }

        $logger->info('Low stock notification job completed.', [
            'items_count'    => $lowStockItems->count(),
            'correlation_id' => $this->correlationId,
        ]);
    }

    /** @return array<int, string> */
    public function tags(): array
    {
        return ['beauty', 'job:low-stock-notification'];
    }
}
