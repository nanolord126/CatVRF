<?php declare(strict_types=1);

namespace Modules\Fashion\Listeners;

use Modules\Fashion\Events\FashionStoreCreated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\StoreWelcomeEmail;

final class SendStoreCreatedNotification
{
    public function handle(FashionStoreCreated $event): void
    {
        $store = $event->store;

        // Send welcome email to store owner
        try {
            Mail::to($store->user->email)->send(new StoreWelcomeEmail($store));
            
            Log::info('Store welcome email sent', [
                'store_id' => $store->id,
                'user_id' => $store->user_id,
                'tenant_id' => $store->tenant_id,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send store welcome email', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
        }

        // Send notification to admins for verification
        // This would typically be implemented with a notification system
        Log::info('Store created, pending verification', [
            'store_id' => $store->id,
            'name' => $store->name,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
