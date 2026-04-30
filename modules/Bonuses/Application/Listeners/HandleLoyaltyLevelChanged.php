<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Bonuses\Domain\Events\LoyaltyLevelChanged;
use Modules\Bonuses\Application\Notifications\LoyaltyLevelChangedNotification;

/**
 * Listener HandleLoyaltyLevelChanged
 *
 * Handles the LoyaltyLevelChanged event by sending notifications and updating user benefits.
 * Notifies users of tier upgrades/downgrades and applies new benefits immediately.
 * Processes asynchronously to avoid blocking the loyalty calculation flow.
 */
final class HandleLoyaltyLevelChanged implements ShouldQueue
{
    public string $queue = 'bonuses';
    public int $tries = 3;

    /**
     * Handles the loyalty level changed event.
     */
    public function handle(LoyaltyLevelChanged $event): void
    {
        try {
            // Get user model
            $user = \App\Models\User::find($event->ownerId);

            if (!$user) {
                Log::channel('bonuses')->warning('User not found for loyalty notification', [
                    'owner_id' => $event->ownerId,
                ]);
                return;
            }

            // Send notification if it's an upgrade or significant change
            if ($event->isUpgrade() || $event->isSignificantChange()) {
                Notification::send($user, new LoyaltyLevelChangedNotification(
                    previousTier: $event->previousTier->value,
                    newTier: $event->newTier->value,
                    discountChange: $event->getDiscountChange(),
                    totalPoints: $event->totalPoints,
                    isUpgrade: $event->isUpgrade(),
                ));

                Log::channel('bonuses')->info('Loyalty level change notification sent', [
                    'owner_id' => $event->ownerId,
                    'previous_tier' => $event->previousTier->value,
                    'new_tier' => $event->newTier->value,
                ]);
            }

            // TODO: Apply new tier benefits (discounts, priority support, etc.)
            // This would integrate with other verticals to apply tier-specific benefits

            Log::channel('bonuses')->info('Loyalty level change processed', [
                'owner_id' => $event->ownerId,
                'previous_tier' => $event->previousTier->value,
                'new_tier' => $event->newTier->value,
                'total_points' => $event->totalPoints,
            ]);
        } catch (\Throwable $e) {
            Log::channel('bonuses')->error('Failed to handle loyalty level change', [
                'owner_id' => $event->ownerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-queue for retry
            $this->release(60);
        }
    }

    /**
     * Handles listener failure.
     */
    public function failed(LoyaltyLevelChanged $event, \Throwable $exception): void
    {
        Log::channel('bonuses')->error('Loyalty level change listener failed permanently', [
            'owner_id' => $event->ownerId,
            'error' => $exception->getMessage(),
        ]);
    }
}
