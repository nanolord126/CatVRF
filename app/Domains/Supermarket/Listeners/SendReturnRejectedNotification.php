<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Listeners;

use App\Domains\Supermarket\Events\ReturnRejected;
use App\Domains\Supermarket\Services\SubscriptionNotificationService;
use App\Domains\Supermarket\Notifications\ReturnRejectedNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final readonly class SendReturnRejectedNotification
{
    public function __construct(
        private SubscriptionNotificationService $notificationService
    ) {}

    public function handle(ReturnRejected $event): void
    {
        try {
            $return = $event->return;
            $buyer = User::find($return->buyer_id);

            if (!$buyer) {
                Log::warning('Return rejected notification failed: user not found', [
                    'return_id' => $return->id,
                    'buyer_id' => $return->buyer_id,
                    'correlation_id' => $event->correlationId,
                ]);
                return;
            }

            // Send notification to buyer
            $buyer->notify(new ReturnRejectedNotification($return));

            // Send to Telegram/WhatsApp if configured
            $this->notificationService->sendReturnRejected($return);

            Log::info('Return rejected notification sent successfully', [
                'return_id' => $return->id,
                'buyer_id' => $return->buyer_id,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send return rejected notification', [
                'return_id' => $event->return->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
        }
    }
}
