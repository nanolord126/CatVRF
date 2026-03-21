<?php declare(strict_types=1);

namespace App\Domains\FashionRetail\Services;

use App\Domains\FashionRetail\Models\FashionRetailProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class ProductService
{
    public function getActive(): Collection
    {
        return FashionRetailProduct::where('status', 'active')
            ->with('shop', 'category', 'variants')
            ->get();
    }

    public function getByShop(int $shopId): Collection
    {
        return FashionRetailProduct::where('shop_id', $shopId)
            ->where('status', 'active')
            ->with('category', 'variants')
            ->get();
    }

    public function getByCategory(int $categoryId): Collection
    {
        return FashionRetailProduct::where('category_id', $categoryId)
            ->where('status', 'active')
            ->with('shop', 'variants')
            ->get();
    }

    public function search(string $query): Collection
    {
        return FashionRetailProduct::where('name', 'like', "%{$query}%")
            ->orWhere('sku', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->where('status', 'active')
            ->with('shop', 'category')
            ->get();
    }

    public function checkStock(int $productId, int $quantity): bool
    {
        $product = FashionRetailProduct::findOrFail($productId);
        return $product->current_stock >= $quantity;
    }

    public function reduceStock(int $productId, int $quantity, string $correlationId): void
    {
        DB::transaction(function () use ($productId, $quantity, $correlationId) {
            $product = FashionRetailProduct::lockForUpdate()->findOrFail($productId);

            if ($product->current_stock < $quantity) {
                throw new \Exception('Insufficient stock');
            }

            $product->update([
                'current_stock' => $product->current_stock - $quantity,
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('FashionRetail stock reduced', [
                'product_id' => $productId,
                'quantity' => $quantity,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function increaseStock(int $productId, int $quantity, string $correlationId): void
    {
        DB::transaction(function () use ($productId, $quantity, $correlationId) {
            $product = FashionRetailProduct::lockForUpdate()->findOrFail($productId);

            $product->update([
                'current_stock' => $product->current_stock + $quantity,
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('FashionRetail stock increased', [
                'product_id' => $productId,
                'quantity' => $quantity,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
