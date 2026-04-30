<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Listeners;

use App\Domains\Supermarket\Events\ReturnCreated;
use App\Domains\Supermarket\Services\SubscriptionNotificationService;
use App\Domains\Supermarket\Notifications\ReturnCreatedNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final readonly class SendReturnNotification
{
    public function __construct(
        private SubscriptionNotificationService $notificationService
    ) {}

    public function handle(ReturnCreated $event): void
    {
        try {
            $return = $event->return;
            $buyer = User::find($return->buyer_id);
            $seller = User::find($return->seller_id);

            if (!$buyer || !$seller) {
                Log::warning('Return notification failed: user not found', [
                    'return_id' => $return->id,
                    'buyer_id' => $return->buyer_id,
                    'seller_id' => $return->seller_id,
                    'correlation_id' => $event->correlationId,
                ]);
                return;
            }

            // Send notification to buyer
            $buyer->notify(new ReturnCreatedNotification($return, 'buyer'));

            // Send notification to seller
            $seller->notify(new ReturnCreatedNotification($return, 'seller'));

            // Send to Telegram/WhatsApp if configured
            $this->notificationService->sendReturnCreated($return);

            Log::info('Return notifications sent successfully', [
                'return_id' => $return->id,
                'buyer_id' => $return->buyer_id,
                'seller_id' => $return->seller_id,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send return notification', [
                'return_id' => $event->return->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
        }
    }
}
