<?php declare(strict_types=1);

namespace Modules\Fashion\Observers;

use App\Domains\Fashion\Models\FashionProduct;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

final class FashionProductObserver
{
    /**
     * Handle the FashionProduct "created" event.
     */
    public function created(FashionProduct $product): void
    {
        // Clear product cache
        Cache::tags(['fashion_products'])->flush();

        // Initialize product analytics
        $product->analytics()->create([
            'tenant_id' => $product->tenant_id,
            'views' => 0,
            'add_to_cart' => 0,
            'purchases' => 0,
        ]);

        Log::info('Fashion product created', [
            'product_id' => $product->id,
            'name' => $product->name,
            'tenant_id' => $product->tenant_id,
            'store_id' => $product->fashion_store_id,
        ]);
    }

    /**
     * Handle the FashionProduct "updated" event.
     */
    public function updated(FashionProduct $product): void
    {
        // Clear product cache
        Cache::tags(['fashion_products'])->flush();

        // If price changed, trigger price update event
        if ($product->isDirty('price_b2c') || $product->isDirty('price_b2b')) {
            Log::info('Fashion product price updated', [
                'product_id' => $product->id,
                'old_price_b2c' => $product->getOriginal('price_b2c'),
                'new_price_b2c' => $product->price_b2c,
                'tenant_id' => $product->tenant_id,
            ]);
        }

        Log::info('Fashion product updated', [
            'product_id' => $product->id,
            'changes' => $product->getDirty(),
            'tenant_id' => $product->tenant_id,
        ]);
    }

    /**
     * Handle the FashionProduct "deleted" event.
     */
    public function deleted(FashionProduct $product): void
    {
        // Clear product cache
        Cache::tags(['fashion_products'])->flush();

        Log::warning('Fashion product deleted', [
            'product_id' => $product->id,
            'name' => $product->name,
            'tenant_id' => $product->tenant_id,
            'store_id' => $product->fashion_store_id,
        ]);
    }
}
