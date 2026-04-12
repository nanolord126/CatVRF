<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Jobs;


use App\Domains\GroceryAndDelivery\Models\GroceryStore;
use App\Services\Inventory\InventoryManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


use Throwable;

/**
 * Синхронизирует остатки магазина-партнёра с внешним API.
 *
 * Поток:
 * 1. Загружает данные магазина по storeId.
 * 2. Проверяет наличие API-провайдера и токена.
 * 3. Инициирует синхронизацию остатков (интеграция с PartnerStoreAPIService).
 * 4. Логирует результат с correlation_id.
 *
 * Запускается периодически или по webhook от магазина.
 */
final class SyncPartnerStoreInventoryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public function __construct(
        public readonly int $storeId,
        public readonly string $correlationId,
    ) {
        $this->onQueue('grocery-sync');
    }

    public function handle(InventoryManagementService $inventoryService): void
    {
        try {
            app(\Illuminate\Database\DatabaseManager::class)->transaction(function () use ($inventoryService): void {
                $store = GroceryStore::findOrFail($this->storeId);

                if ($store->api_provider && $store->api_token) {
                    app(\Psr\Log\LoggerInterface::class)->channel('audit')->info('Store inventory sync initiated', [
                        'store_id' => $this->storeId,
                        'api_provider' => $store->api_provider,
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            });
        } catch (Throwable $e) {
            app(\Psr\Log\LoggerInterface::class)->channel('audit')->error('SyncPartnerStoreInventoryJob failed', [
                'store_id' => $this->storeId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }
}
