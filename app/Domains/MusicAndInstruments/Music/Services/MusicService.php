<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

final readonly class MusicService
{
    public function __construct(private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


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
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($data) {
                $correlationId = (string) Str::uuid();

                $store = MusicStore::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                ]));

                $this->logger->info('Music store created', [
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
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

            $this->db->transaction(function () use ($accessoryId, $quantity, $reason) {
                $accessory = MusicAccessory::lockForUpdate()->findOrFail($accessoryId);

                if ($accessory->stock < $quantity) {
                    throw new \RuntimeException('Insufficient stock for accessory: ' . $accessory->name);
                }

                $accessory->decrement('stock', $quantity);

                $this->logger->info('Music accessory stock deducted', [
                    'accessory_id' => $accessoryId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'correlation_id' => $this->request->header('X-Correlation-ID'),
                ]);
            });
        }

        /**
         * Update store rating.
         */
        public function updateStoreRating(int $storeId): void
        {
            $store = MusicStore::findOrFail($storeId);

            $avgRating = $this->db->table('music_reviews')
                ->where('music_store_id', $storeId)
                ->where('is_published', true)
                ->avg('rating');

            $count = $this->db->table('music_reviews')
                ->where('music_store_id', $storeId)
                ->where('is_published', true)
                ->count();

            $store->update([
                'rating' => round((float)$avgRating, 2),
                'review_count' => $count,
            ]);
        }
}
