<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Bonuses\Domain\Events\BonusAwarded;
use Modules\Bonuses\Application\Notifications\BonusAwardedNotification;

/**
 * Listener SendBonusNotification
 *
 * Handles the BonusAwarded event by sending notifications to the user.
 * Sends push notifications, email, or in-app messages based on user preferences.
 * Processes asynchronously to avoid blocking the award flow.
 */
final class SendBonusNotification implements ShouldQueue
{
    public string $queue = 'notifications';
    public int $tries = 3;

    /**
     * Handles the bonus awarded event by sending notifications.
     */
    public function handle(BonusAwarded $event): void
    {
        try {
            // Get user model (assuming user ID corresponds to a User model)
            $user = \App\Models\User::find($event->ownerId);

            if (!$user) {
                Log::channel('bonuses')->warning('User not found for bonus notification', [
                    'owner_id' => $event->ownerId,
                    'bonus_id' => $event->bonusId,
                ]);
                return;
            }

            // Send notification
            Notification::send($user, new BonusAwardedNotification(
                bonusId: $event->bonusId,
                amount: $event->amount->getAmount(),
                type: $event->type->value,
                expiresAt: $event->expiresAt,
            ));

            Log::channel('bonuses')->info('Bonus notification sent', [
                'bonus_id' => $event->bonusId,
                'owner_id' => $event->ownerId,
                'amount' => $event->amount->getAmount(),
            ]);
        } catch (\Throwable $e) {
            Log::channel('bonuses')->error('Failed to send bonus notification', [
                'bonus_id' => $event->bonusId,
                'owner_id' => $event->ownerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't block on notification failures
            $this->delete();
        }
    }

    /**
     * Handles listener failure.
     */
    public function failed(BonusAwarded $event, \Throwable $exception): void
    {
        Log::channel('bonuses')->error('Bonus notification listener failed permanently', [
            'bonus_id' => $event->bonusId,
            'owner_id' => $event->ownerId,
            'error' => $exception->getMessage(),
        ]);
    }
}
