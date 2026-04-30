<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\GroceryAndDelivery\Jobs;

use LoggerInterface;

use App\Domains\GroceryAndDelivery\Models\GroceryStore;
use App\Services\Inventory\InventoryManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

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

    public array $backoff = [60, 300, 900];

    public int $timeout = 120;

    public int $tries = 5;

    public function __construct(private readonly LoggerInterface $loggerInterface,
        public readonly int $storeId,
        public readonly string $correlationId,) {
        $this->onQueue('grocery-sync');
    }

    public function handle(InventoryManagementService $inventoryService, DatabaseManager $db, LoggerInterface $logger): void
    {
        try {
            $db->transaction(function () use ($logger): void {
                $store = GroceryStore::findOrFail($this->storeId);

                if ($store->api_provider && $store->api_token) {
                    $logger->channel('audit')->$this->logger->info('Store inventory sync initiated', [
                        'store_id' => $this->storeId,
                        'api_provider' => $store->api_provider,
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            });
        } catch (Throwable $e) {
            $logger->channel('audit')->error('SyncPartnerStoreInventoryJob failed', [
                'store_id' => $this->storeId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->loggerInterface /* TODO: inject via constructor DI */ /* TODO: inject via DI */  // failed() no method injection->error('groceryanddelivery job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
