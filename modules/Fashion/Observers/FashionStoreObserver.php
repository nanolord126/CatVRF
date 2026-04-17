<?php declare(strict_types=1);

namespace Modules\Fashion\Observers;

use App\Domains\Fashion\Models\FashionStore;
use Illuminate\Support\Facades\Log;

final class FashionStoreObserver
{
    /**
     * Handle the FashionStore "created" event.
     */
    public function created(FashionStore $store): void
    {
        // Auto-create wallet account for the store
        if (!$store->wallet) {
            $store->wallet()->create([
                'name' => 'Fashion Store Wallet',
                'type' => 'fashion_store',
                'tenant_id' => $store->tenant_id,
                'owner_id' => $store->user_id,
                'balance' => 0,
                'currency' => 'RUB',
            ]);

            Log::info('Wallet account created for fashion store', [
                'store_id' => $store->id,
                'tenant_id' => $store->tenant_id,
                'user_id' => $store->user_id,
            ]);
        }

        // Initialize store analytics
        $store->analytics()->create([
            'tenant_id' => $store->tenant_id,
            'views' => 0,
            'orders' => 0,
            'revenue' => 0,
        ]);

        Log::info('Fashion store created', [
            'store_id' => $store->id,
            'name' => $store->name,
            'tenant_id' => $store->tenant_id,
        ]);
    }

    /**
     * Handle the FashionStore "updated" event.
     */
    public function updated(FashionStore $store): void
    {
        Log::info('Fashion store updated', [
            'store_id' => $store->id,
            'changes' => $store->getDirty(),
            'tenant_id' => $store->tenant_id,
        ]);
    }

    /**
     * Handle the FashionStore "deleted" event.
     */
    public function deleted(FashionStore $store): void
    {
        Log::warning('Fashion store deleted', [
            'store_id' => $store->id,
            'name' => $store->name,
            'tenant_id' => $store->tenant_id,
        ]);
    }
}
