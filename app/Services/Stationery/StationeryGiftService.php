<?php declare(strict_types=1);

namespace App\Services\Stationery;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StationeryGiftService extends Model
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
         * Composes a new gift set from individual products.
         * Calculates total price with optional discount.
         */
        public function createGiftSet(array $data, array $productIds): StationeryGiftSet
        {
            Log::channel('audit')->info('Attempting to create stationery gift set', [
                'name' => $data['name'],
                'product_count' => count($productIds),
                'correlation_id' => $this->correlationId,
            ]);

            return DB::transaction(function () use ($data, $productIds) {
                // Validate all products exist and belong to the same tenant
                $products = StationeryProduct::whereIn('id', $productIds)->get();

                if ($products->count() !== count($productIds)) {
                    throw new \InvalidArgumentException('One or more stationery products not found.');
                }

                $totalPrice = $products->sum('price_cents');

                // Apply 10% discount for sets by default
                $setPrice = (int) ($totalPrice * 0.9);

                $giftSet = StationeryGiftSet::create(array_merge($data, [
                    'price_cents' => $setPrice,
                    'product_ids' => $productIds,
                    'correlation_id' => $this->correlationId,
                ]));

                Log::channel('audit')->info('Stationery gift set created', [
                    'uuid' => $giftSet->uuid,
                    'set_price' => $setPrice,
                    'correlation_id' => $this->correlationId,
                ]);

                return $giftSet;
            });
        }

        /**
         * Handles order processing for a gift set with optional wrapping.
         */
        public function purchaseGiftSet(int $userId, int $giftSetId, bool $withWrapping = false): bool
        {
            $this->fraud->check([
                'operation' => 'gift_set_purchase',
                'user_id' => $userId,
                'gift_set_id' => $giftSetId,
                'correlation_id' => $this->correlationId,
            ]);

            return DB::transaction(function () use ($userId, $giftSetId, $withWrapping) {
                $giftSet = StationeryGiftSet::findOrFail($giftSetId);

                $finalPrice = $giftSet->price_cents;

                if ($withWrapping) {
                    // Fixed wrapping fee (100 RUB)
                    $finalPrice += 10000;
                }

                // Wallet debit for the user
                $this->wallet->debit($userId, $finalPrice, 'Stationery Gift Set Purchase', $this->correlationId);

                // Audit the transaction
                Log::channel('audit')->info('Gift set purchased successfully', [
                    'user_id' => $userId,
                    'gift_set' => $giftSetId,
                    'final_price' => $finalPrice,
                    'wrapping' => $withWrapping,
                    'correlation_id' => $this->correlationId,
                ]);

                return true;
            });
        }

        /**
         * Retrieves seasonal gift collections for promotional displays.
         */
        public function getSeasonalCollections(int $tenantId): \Illuminate\Support\Collection
        {
            return StationeryGiftSet::where('tenant_id', $tenantId)
                ->where('is_seasonal', true)
                ->with('store')
                ->latest()
                ->limit(10)
                ->get();
        }
}
