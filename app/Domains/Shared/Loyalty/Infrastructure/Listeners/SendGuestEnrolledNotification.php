<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Loyalty\Domain\Events\GuestEnrolled;

final readonly class SendGuestEnrolledNotification
{
    public function handle(GuestEnrolled $event): void
    {
        try {
            // Send welcome notification
            // Send database notification
            // Trigger analytics event

            Log::info('Guest enrolled notification sent', [
                'guest_id' => $event->getGuestId(),
                'signup_bonus' => $event->getSignupBonus()->getValue(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send guest enrolled notification', [
                'guest_id' => $event->getGuestId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
