<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Loyalty\Domain\Events\PointsEarned;

final readonly class SendPointsEarnedNotification
{
    public function handle(PointsEarned $event): void
    {
        try {
            // Send push notification
            // Send database notification
            // Trigger analytics event

            Log::info('Points earned notification sent', [
                'guest_id' => $event->getGuestId(),
                'points' => $event->getPointsEarned()->getValue(),
                'balance' => $event->getBalanceAfter()->getValue(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send points earned notification', [
                'guest_id' => $event->getGuestId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
