<?php

declare(strict_types=1);

namespace App\Jobs\Inventory;

use Psr\Log\LoggerInterface;

use App\Services\InventoryManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Carbon\CarbonImmutable;

final class LowStockAlertJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private readonly string $correlationId;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,) {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('notification');
    }

    public function tags(): array
    {
        return ['inventory', 'stock-alert', 'multi-vertical'];
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addHours(2);
    }

    public function handle(InventoryManagementService $inventoryService): void
    {
        try {
            $this->db->transaction(function () use ($inventoryService) {
                $lowStockItems = $inventoryService->checkLowStock();

                if ($lowStockItems->isEmpty()) {
                    $this->logger->channel('audit')->$this->logger->info('Low stock check completed - no items below threshold', [
                        'correlation_id' => $this->correlationId,
                    ]);

                    return;
                }

                $lowStockItems->each(function ($item) {
                    $this->logger->channel('audit')->warning('Item below minimum stock threshold', [
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
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->logger->channel('audit')->error('Low stock alert job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
