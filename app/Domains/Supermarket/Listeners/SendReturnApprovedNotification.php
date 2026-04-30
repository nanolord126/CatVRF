<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Listeners;

use App\Domains\Supermarket\Events\ReturnApproved;
use App\Domains\Supermarket\Services\SubscriptionNotificationService;
use App\Domains\Supermarket\Notifications\ReturnApprovedNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final readonly class SendReturnApprovedNotification
{
    public function __construct(
        private SubscriptionNotificationService $notificationService
    ) {}

    public function handle(ReturnApproved $event): void
    {
        try {
            $return = $event->return;
            $buyer = User::find($return->buyer_id);

            if (!$buyer) {
                Log::warning('Return approved notification failed: user not found', [
                    'return_id' => $return->id,
                    'buyer_id' => $return->buyer_id,
                    'correlation_id' => $event->correlationId,
                ]);
                return;
            }

            // Send notification to buyer
            $buyer->notify(new ReturnApprovedNotification($return));

            // Send to Telegram/WhatsApp if configured
            $this->notificationService->sendReturnApproved($return);

            Log::info('Return approved notification sent successfully', [
                'return_id' => $return->id,
                'buyer_id' => $return->buyer_id,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send return approved notification', [
                'return_id' => $event->return->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
        }
    }
}
