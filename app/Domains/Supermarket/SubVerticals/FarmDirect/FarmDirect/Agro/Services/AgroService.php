<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\FarmDirect\Agro\Services;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Database\DatabaseManager;

final readonly class AgroService
{
    private readonly string $correlationId;

    public function __construct(private readonly BusDispatcher $bus,
        ?string $correlationId,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard) {
        $this->correlationId = $correlationId ?? (string) Str::uuid();
    }

    /**
     * Создание/регистрация агро-предприятия
     */
    public function registerFarm(array $data, int $tenantId): AgroFarm
    {
        // Fraud Check (защита от массовой регистрации фейковых ферм)
        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($data, $tenantId) {
            $farm = AgroFarm::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'inn' => $data['inn'],
                'specialization' => $data['specialization'] ?? [],
                'correlation_id' => $this->correlationId,
            ]);

            $this->logger->$this->logger->info('Agro farm registered', [
                'farm_id' => $farm->id,
                'tenant_id' => $tenantId,
                'correlation_id' => $this->correlationId,
            ]);

            return $farm;
        });
    }

    /**
     * Обновление складских остатков агро-продукции
     */
    public function updateStock(int $productId, float $quantity, string $reason = 'manual_update'): bool
    {
        return $this->db->transaction(function () use ($productId, $quantity, $reason) {
            $product = AgroProduct::lockForUpdate()->findOrFail($productId);

            $oldStock = $product->current_stock;
            $product->current_stock = $quantity;
            $product->save();

            $this->logger->$this->logger->info('Agro stock updated', [
                'product_id' => $productId,
                'old_stock' => $oldStock,
                'new_stock' => $quantity,
                'reason' => $reason,
                'correlation_id' => $this->correlationId,
            ]);

            // Если остаток ниже порога — генерируем алерт (в будущем асинхронный Job)
            if ($product->current_stock <= $product->min_stock_alert) {
                // LowStockNotificationJob::$this->bus->dispatch(...)
            }

            return true;
        });
    }
}
