<?php

declare(strict_types=1);

namespace Modules\Fashion\Listeners;

use Modules\Fashion\Events\FashionOrderPlaced;
use Illuminate\Log\LogManager;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Cache\Repository;

final class ProcessOrderPlaced
{
    public function __construct(
        private readonly LogManager $log,
        private readonly ConnectionInterface $db,
        private readonly Repository $cache,
    ) {}

    public function handle(FashionOrderPlaced $event): void
    {
        $order = $event->order;

        // Update inventory
        foreach ($order->items as $item) {
            $this->db->table('fashion_products')
                ->where('id', $item->fashion_product_id)
                ->decrement('available_stock', $item->quantity);

            // Check if product is now out of stock
            $product = $this->db->table('fashion_products')
                ->where('id', $item->fashion_product_id)
                ->first();

            if ($product && $product->available_stock <= 0) {
                // Trigger out of stock event
                $this->log->warning('Product out of stock after order', [
                    'product_id' => $item->fashion_product_id,
                    'order_id' => $order->id,
                    'correlation_id' => $event->correlationId,
                ]);
            }
        }

        // Clear user cart
        $this->db->table('fashion_carts')
            ->where('user_id', $order->user_id)
            ->where('fashion_store_id', $order->fashion_store_id)
            ->delete();

        // Update store analytics
        $this->db->table('fashion_store_analytics')
            ->where('fashion_store_id', $order->fashion_store_id)
            ->where('tenant_id', $order->tenant_id)
            ->increment('orders', 1);

        // Clear cache
        $this->cache->tags(['fashion_orders', 'fashion_products'])->flush();

        $this->log->info('Order processed successfully', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'tenant_id' => $order->tenant_id,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
