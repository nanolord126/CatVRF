<?php declare(strict_types=1);

namespace App\Domains\FashionRetail\Services;

use App\Domains\FashionRetail\Models\FashionRetailShop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class ShopService
{
    public function getActive(): Collection
    {
        return FashionRetailShop::where('is_active', true)
            ->orderBy('rating', 'desc')
            ->with('products')
            ->get();
    }

    public function getByOwner(int $ownerId): Collection
    {
        return FashionRetailShop::where('owner_id', $ownerId)
            ->with('products', 'orders')
            ->get();
    }

    public function search(string $query): Collection
    {
        return FashionRetailShop::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->where('is_active', true)
            ->get();
    }

    public function verifyShop(int $shopId, string $correlationId): void
    {
        DB::transaction(function () use ($shopId, $correlationId) {
            $shop = FashionRetailShop::lockForUpdate()->findOrFail($shopId);

            $shop->update([
                'is_verified' => true,
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('FashionRetail shop verified', [
                'shop_id' => $shopId,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function deactivateShop(int $shopId, string $correlationId): void
    {
        DB::transaction(function () use ($shopId, $correlationId) {
            $shop = FashionRetailShop::lockForUpdate()->findOrFail($shopId);

            $shop->update([
                'is_active' => false,
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('FashionRetail shop deactivated', [
                'shop_id' => $shopId,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function getStats(int $shopId): array
    {
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
