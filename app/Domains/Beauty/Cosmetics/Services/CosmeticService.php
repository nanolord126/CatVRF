<?php declare(strict_types=1);

namespace App\Domains\Beauty\Cosmetics\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CosmeticService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
            private readonly string $correlationId = '',
        ) {
            $this->correlationId = $correlationId ?: Str::uuid()->toString();
        }

        public function orderProduct(int $productId, int $quantity, int $userId, int $tenantId): CosmeticOrder
        {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($productId, $quantity, $userId, $tenantId) {
                $product = CosmeticProduct::lockForUpdate()->find($productId);

                if (!$product || $product->stock < $quantity) {
                    throw new \Exception('Insufficient stock');
                }

                $order = CosmeticOrder::create([
                    'tenant_id' => $tenantId,
                    'uuid' => Str::uuid(),
                    'correlation_id' => $this->correlationId,
                    'product_id' => $productId,
                    'user_id' => $userId,
                    'quantity' => $quantity,
                    'total_price' => $product->price * $quantity,
                    'status' => 'pending',
                ]);

                Log::channel('audit')->info('Cosmetic order created', [
                    'correlation_id' => $this->correlationId,
                    'product_id' => $productId,
                ]);

                return $order;
            });
        }
}
