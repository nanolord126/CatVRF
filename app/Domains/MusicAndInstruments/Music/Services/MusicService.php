<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Get all music stores for the current tenant.
         */
        public function getStores(): Collection
        {
            return MusicStore::all();
        }

        /**
         * Create a new music store.
         */
        public function createStore(array $data): MusicStore
        {
            FraudControlService::check();

            return DB::transaction(function () use ($data) {
                $correlationId = (string) Str::uuid();

                $store = MusicStore::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                ]));

                Log::channel('audit')->info('Music store created', [
                    'store_id' => $store->id,
                    'correlation_id' => $correlationId,
                ]);

                return $store;
            });
        }

        /**
         * Search instruments with filters.
         */
        public function searchInstruments(array $filters): Collection
        {
            $query = MusicInstrument::query();

            if (isset($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            if (isset($filters['brand'])) {
                $query->where('brand', $filters['brand']);
            }

            if (isset($filters['min_price'])) {
                $query->where('price_cents', '>=', $filters['min_price']);
            }

            if (isset($filters['max_price'])) {
                $query->where('price_cents', '<=', $filters['max_price']);
            }

            return $query->get();
        }

        /**
         * Deduct accessory stock.
         */
        public function deductAccessoryStock(int $accessoryId, int $quantity, string $reason): void
        {
            FraudControlService::check();

            DB::transaction(function () use ($accessoryId, $quantity, $reason) {
                $accessory = MusicAccessory::lockForUpdate()->findOrFail($accessoryId);

                if ($accessory->stock < $quantity) {
                    throw new \Exception('Insufficient stock for accessory: ' . $accessory->name);
                }

                $accessory->decrement('stock', $quantity);

                Log::channel('audit')->info('Music accessory stock deducted', [
                    'accessory_id' => $accessoryId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);
            });
        }

        /**
         * Update store rating.
         */
        public function updateStoreRating(int $storeId): void
        {
            $store = MusicStore::findOrFail($storeId);

            $avgRating = DB::table('music_reviews')
                ->where('music_store_id', $storeId)
                ->where('is_published', true)
                ->avg('rating');

            $count = DB::table('music_reviews')
                ->where('music_store_id', $storeId)
                ->where('is_published', true)
                ->count();

            $store->update([
                'rating' => round((float)$avgRating, 2),
                'review_count' => $count,
            ]);
        }
}
