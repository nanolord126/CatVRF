<?php

declare(strict_types=1);

namespace Modules\Fashion\Listeners;

use Modules\Fashion\Events\FashionStoreCreated;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Mail\Mailer;
use App\Mail\StoreWelcomeEmail;

final class SendStoreCreatedNotification
{
    public function __construct(
        private readonly LogManager $log,
        private readonly Mailer $mailer,
    ) {}

    public function handle(FashionStoreCreated $event): void
    {
        $store = $event->store;

        // Send welcome email to store owner
        try {
            $this->mailer->to($store->user->email)->send(new StoreWelcomeEmail($store));

            $this->log->info('Store welcome email sent', [
                'store_id' => $store->id,
                'user_id' => $store->user_id,
                'tenant_id' => $store->tenant_id,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Exception $e) {
            $this->log->error('Failed to send store welcome email', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
        }

        // Send notification to admins for verification
        // This would typically be implemented with a notification system
        $this->log->info('Store created, pending verification', [
            'store_id' => $store->id,
            'name' => $store->name,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
