<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    private string $correlation_id;

        public function __construct(
            private FraudControlService $fraud,
        ) {
            $this->correlation_id = request()->header('X-Correlation-ID', Str::uuid()->toString());
        }

        /**
         * ПРАВИЛО КОРЗИН 2026: 1 продавец = 1 корзина.
         * Максимум 20 корзин на пользователя.
         */
        public function getCartForUser(int $userId, int $storeId): Collection
        {
            return DB::table('fashion_carts')
                ->where('user_id', $userId)
                ->where('fashion_store_id', $storeId)
                ->where('tenant_id', filament()->getTenant()?->id)
                ->get();
        }

        /**
         * РЕЗЕРВИРОВАНИЕ ТОВАРА (20 МИНУТ)
         */
        public function reserveItem(int $productId, int $quantity, int $userId): bool
        {
            return DB::transaction(function () use ($productId, $quantity, $userId) {
                $product = FashionProduct::lockForUpdate()->find($productId);

                if (!$product || $product->available_stock < $quantity) {
                    Log::channel('audit')->warning('Insufficient stock for reservation', [
                        'product_id' => $productId,
                        'correlation_id' => $this->correlation_id,
                    ]);
                    return false;
                }

                // Увеличиваем резерв
                $product->increment('reserve_quantity', $quantity);

                // Записываем в таблицу резервов с временем истечения (20 мин)
                DB::table('fashion_item_reserves')->insert([
                    'product_id' => $productId,
                    'user_id' => $userId,
                    'quantity' => $quantity,
                    'expires_at' => now()->addMinutes(20),
                    'correlation_id' => $this->correlation_id,
                    'created_at' => now(),
                ]);

                Log::channel('audit')->info('Item reserved for 20 minutes', [
                    'product_id' => $productId,
                    'qty' => $quantity,
                    'correlation_id' => $this->correlation_id,
                ]);

                return true;
            });
        }

        /**
         * B2B ПРОВЕРКА И ЦЕНООБРАЗОВАНИЕ
         */
        public function calculateB2BPrice(FashionProduct $product, string $inn): int
        {
            $this->fraud->check('b2b_price_request', ['inn' => $inn, 'product_id' => $product->id]);

            // Проверяем наличие B2B цены. Если нет — отдаем B2C.
            return $product->price_b2b ?? $product->price_b2c;
        }

        /**
         * ЛОГИКА ОТОБРАЖЕНИЯ ЦЕНЫ 2026
         */
        public function getDisplayPrice(FashionProduct $product): array
        {
            $currentPrice = $product->price_b2c;
            $oldPrice = $product->old_price;

            return [
                'price' => $currentPrice,
                'old_price' => $oldPrice,
                'is_discounted' => $oldPrice > $currentPrice,
                'currency' => 'RUB',
            ];
        }
}
