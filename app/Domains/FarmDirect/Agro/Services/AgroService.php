<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Agro\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class AgroService
{

    private string $correlationId;

        public function __construct(?string $correlationId = null,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard)
        {
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

                $this->logger->info('Agro farm registered', [
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

                $this->logger->info('Agro stock updated', [
                    'product_id' => $productId,
                    'old_stock' => $oldStock,
                    'new_stock' => $quantity,
                    'reason' => $reason,
                    'correlation_id' => $this->correlationId,
                ]);

                // Если остаток ниже порога — генерируем алерт (в будущем асинхронный Job)
                if ($product->current_stock <= $product->min_stock_alert) {
                    // LowStockNotificationJob::dispatch(...)
                }

                return true;
            });
        }
}
