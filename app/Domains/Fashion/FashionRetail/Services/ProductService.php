<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class ProductService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    public function getActive(): Collection
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            return FashionRetailProduct::where('status', 'active')
                ->with('shop', 'category', 'variants')
                ->get();
        }

        public function getByShop(int $shopId): Collection
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            return FashionRetailProduct::where('shop_id', $shopId)
                ->where('status', 'active')
                ->with('category', 'variants')
                ->get();
        }

        public function getByCategory(int $categoryId): Collection
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            return FashionRetailProduct::where('category_id', $categoryId)
                ->where('status', 'active')
                ->with('shop', 'variants')
                ->get();
        }

        public function search(string $query): Collection
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            return FashionRetailProduct::where('name', 'like', "%{$query}%")
                ->orWhere('sku', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->where('status', 'active')
                ->with('shop', 'category')
                ->get();
        }

        public function checkStock(int $productId, int $quantity): bool
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            $product = FashionRetailProduct::findOrFail($productId);
            return $product->current_stock >= $quantity;
        }

        public function reduceStock(int $productId, int $quantity, string $correlationId): void
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

            $this->db->transaction(function () use ($productId, $quantity, $correlationId) {
                $product = FashionRetailProduct::lockForUpdate()->findOrFail($productId);

                if ($product->current_stock < $quantity) {
                    throw new \RuntimeException('Insufficient stock');
                }

                $product->update([
                    'current_stock' => $product->current_stock - $quantity,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('FashionRetail stock reduced', [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        public function increaseStock(int $productId, int $quantity, string $correlationId): void
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            $this->db->transaction(function () use ($productId, $quantity, $correlationId) {
                $product = FashionRetailProduct::lockForUpdate()->findOrFail($productId);

                $product->update([
                    'current_stock' => $product->current_stock + $quantity,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('FashionRetail stock increased', [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'correlation_id' => $correlationId,
                ]);
            });
        }
}
