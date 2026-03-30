<?php declare(strict_types=1);

namespace App\Services\Collectibles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CollectibleService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private FraudControlService $fraud,
            private WalletService $wallet,
            private string $correlationId = ''
        ) {
            $this->correlationId = $correlationId ?: (string) Str::uuid();
        }

        /**
         * Executes a direct purchase of a collectible item.
         * @throws \Exception
         */
        public function purchase(int $userId, int $itemId, string $type = 'b2c'): CollectibleOrder
        {
            $item = CollectibleItem::with('store')->findOrFail($itemId);

            // 1. Pre-purchase Fraud Check
            $this->fraud->check([
                'operation' => 'collectible_purchase',
                'user_id' => $userId,
                'item_id' => $itemId,
                'amount' => $item->price_cents,
                'correlation_id' => $this->correlationId,
            ]);

            return DB::transaction(function () use ($userId, $item, $type) {
                // 2. Financial settlement via WalletService
                $this->wallet->debit($userId, $item->price_cents, "Purchase of collectible: {$item->name}");

                // Platform commission (14% per CAÑON)
                $commission = (int) ($item->price_cents * 0.14);
                $sellerAmount = $item->price_cents - $commission;

                $this->wallet->credit($item->store->tenant_id, $sellerAmount, "Sale of collectible: {$item->name}");

                // 3. Order record creation
                $order = CollectibleOrder::create([
                    'user_id' => $userId,
                    'item_id' => $item->id,
                    'total_cents' => $item->price_cents,
                    'status' => 'completed',
                    'type' => $type,
                    'correlation_id' => $this->correlationId,
                ]);

                // 4. Update item ownership/availability
                $item->update(['collection_id' => $this->getOrCreateUserCollection($userId, $item->category->name)]);

                Log::channel('audit')->info('Collectible purchase finalized', [
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'item_id' => $item->id,
                    'correlation_id' => $this->correlationId,
                ]);

                return $order;
            });
        }

        /**
         * Register a new collectible item with automated authenticity verification attempt.
         */
        public function registerItem(array $data): CollectibleItem
        {
            return DB::transaction(function () use ($data) {
                $item = CollectibleItem::create(array_merge($data, [
                    'correlation_id' => $this->correlationId,
                ]));

                Log::channel('audit')->info('New collectible item registered', [
                    'item_id' => $item->id,
                    'store_id' => $item->store_id,
                    'correlation_id' => $this->correlationId,
                ]);

                return $item;
            });
        }

        private function getOrCreateUserCollection(int $userId, string $categoryName): int
        {
            $collection = \App\Models\Collectibles\UserCollection::firstOrCreate([
                'user_id' => $userId,
                'name' => "My {$categoryName} Collection",
            ]);

            return $collection->id;
        }
}
