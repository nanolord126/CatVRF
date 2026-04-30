<?php

declare(strict_types=1);

namespace Modules\Fashion\Observers;

use App\Domains\Fashion\Models\FashionOrder;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Cache\Repository;

final class FashionOrderObserver
{
    public function __construct(
        private readonly LogManager $log,
        private readonly Repository $cache,
    ) {}

    /**
     * Handle the FashionOrder "created" event.
     */
    public function created(FashionOrder $order): void
    {
        // Clear order cache
        $this->cache->tags(['fashion_orders'])->flush();

        // Update store order count
        if ($order->store) {
            $order->store->increment('total_orders');
        }

        $this->log->info('Fashion order created', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'tenant_id' => $order->tenant_id,
            'store_id' => $order->fashion_store_id,
            'amount' => $order->total_amount,
        ]);
    }

    /**
     * Handle the FashionOrder "updated" event.
     */
    public function updated(FashionOrder $order): void
    {
        // Clear order cache
        $this->cache->tags(['fashion_orders'])->flush();

        // If status changed to completed, update store revenue
        if ($order->isDirty('status') && $order->status === 'completed') {
            if ($order->store) {
                $order->store->increment('total_revenue', $order->total_amount);
            }

            $this->log->info('Fashion order completed', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'tenant_id' => $order->tenant_id,
                'amount' => $order->total_amount,
            ]);
        }

        // If status changed to cancelled, restore stock
        if ($order->isDirty('status') && $order->status === 'cancelled') {
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('available_stock', $item->quantity);
                }
            }

            $this->log->info('Fashion order cancelled, stock restored', [
                'order_id' => $order->id,
                'tenant_id' => $order->tenant_id,
            ]);
        }

        $this->log->info('Fashion order updated', [
            'order_id' => $order->id,
            'changes' => $order->getDirty(),
            'tenant_id' => $order->tenant_id,
        ]);
    }

    /**
     * Handle the FashionOrder "deleted" event.
     */
    public function deleted(FashionOrder $order): void
    {
        // Clear order cache
        $this->cache->tags(['fashion_orders'])->flush();

        $this->log->warning('Fashion order deleted', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'tenant_id' => $order->tenant_id,
        ]);
    }
}
