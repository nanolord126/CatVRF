<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class ShopService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    public function getActive(): Collection
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            return FashionRetailShop::where('is_active', true)
                ->orderBy('rating', 'desc')
                ->with('products')
                ->get();
        }

        public function getByOwner(int $ownerId): Collection
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            return FashionRetailShop::where('owner_id', $ownerId)
                ->with('products', 'orders')
                ->get();
        }

        public function search(string $query): Collection
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            return FashionRetailShop::where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->where('is_active', true)
                ->get();
        }

        public function verifyShop(int $shopId, string $correlationId): void
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

            $this->db->transaction(function () use ($shopId, $correlationId) {
                $shop = FashionRetailShop::lockForUpdate()->findOrFail($shopId);

                $shop->update([
                    'is_verified' => true,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('FashionRetail shop verified', [
                    'shop_id' => $shopId,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        public function deactivateShop(int $shopId, string $correlationId): void
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            $this->db->transaction(function () use ($shopId, $correlationId) {
                $shop = FashionRetailShop::lockForUpdate()->findOrFail($shopId);

                $shop->update([
                    'is_active' => false,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('FashionRetail shop deactivated', [
                    'shop_id' => $shopId,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        public function getStats(int $shopId): array
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            $shop = FashionRetailShop::with('products', 'orders')->findOrFail($shopId);

            return [
                'shop_id' => $shopId,
                'total_products' => $shop->products->count(),
                'total_orders' => $shop->orders->count(),
                'rating' => $shop->rating,
                'review_count' => $shop->review_count,
                'is_verified' => $shop->is_verified,
            ];
        }
}
