<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\FraudControlService;


use App\Domains\Fashion\FashionRetail\Models\FashionRetailProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class ProductService
{
    public function getActive(): Collection
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

        return FashionRetailProduct::where('status', 'active')
            ->with('shop', 'category', 'variants')
            ->get();
    }

    public function getByShop(int $shopId): Collection
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

        return FashionRetailProduct::where('shop_id', $shopId)
            ->where('status', 'active')
            ->with('category', 'variants')
            ->get();
    }

    public function getByCategory(int $categoryId): Collection
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

        return FashionRetailProduct::where('category_id', $categoryId)
            ->where('status', 'active')
            ->with('shop', 'variants')
            ->get();
    }

    public function search(string $query): Collection
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

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
        Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

        $product = FashionRetailProduct::findOrFail($productId);
        return $product->current_stock >= $quantity;
    }

    public function reduceStock(int $productId, int $quantity, string $correlationId): void
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

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
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

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
