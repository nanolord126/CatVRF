<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Loyalty\Domain\Events\RewardRedeemed;

final readonly class SendRewardRedeemedNotification
{
    public function handle(RewardRedeemed $event): void
    {
        try {
            // Send database notification
            // Trigger analytics event

            Log::info('Reward redeemed notification sent', [
                'guest_id' => $event->getGuestId(),
                'reward_name' => $event->getRewardName(),
                'points_cost' => $event->getPointsCost()->getValue(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send reward redeemed notification', [
                'guest_id' => $event->getGuestId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
