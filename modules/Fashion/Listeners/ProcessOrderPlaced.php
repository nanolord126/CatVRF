<?php declare(strict_types=1);

namespace Modules\Fashion\Listeners;

use Modules\Fashion\Events\FashionOrderPlaced;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

final class ProcessOrderPlaced
{
    public function handle(FashionOrderPlaced $event): void
    {
        $order = $event->order;

        // Update inventory
        foreach ($order->items as $item) {
            DB::table('fashion_products')
                ->where('id', $item->fashion_product_id)
                ->decrement('available_stock', $item->quantity);
            
            // Check if product is now out of stock
            $product = DB::table('fashion_products')
                ->where('id', $item->fashion_product_id)
                ->first();
            
            if ($product && $product->available_stock <= 0) {
                // Trigger out of stock event
                Log::warning('Product out of stock after order', [
                    'product_id' => $item->fashion_product_id,
                    'order_id' => $order->id,
                    'correlation_id' => $event->correlationId,
                ]);
            }
        }

        // Clear user cart
        DB::table('fashion_carts')
            ->where('user_id', $order->user_id)
            ->where('fashion_store_id', $order->fashion_store_id)
            ->delete();

        // Update store analytics
        DB::table('fashion_store_analytics')
            ->where('fashion_store_id', $order->fashion_store_id)
            ->where('tenant_id', $order->tenant_id)
            ->increment('orders', 1);

        // Clear cache
        Cache::tags(['fashion_orders', 'fashion_products'])->flush();

        Log::info('Order processed successfully', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'tenant_id' => $order->tenant_id,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
