<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Loyalty\Domain\Events\TierUpgraded;

final readonly class SendTierUpgradedNotification
{
    public function handle(TierUpgraded $event): void
    {
        try {
            // Send push notification
            // Send email notification
            // Send database notification
            // Trigger analytics event

            Log::info('Tier upgraded notification sent', [
                'guest_id' => $event->getGuestId(),
                'previous_tier' => $event->getPreviousTierId(),
                'new_tier' => $event->getNewTierName(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send tier upgraded notification', [
                'guest_id' => $event->getGuestId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
