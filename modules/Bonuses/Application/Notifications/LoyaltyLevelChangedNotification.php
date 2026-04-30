<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification LoyaltyLevelChangedNotification
 *
 * Notification sent to users when their loyalty tier changes.
 * Celebrates upgrades and informs about new benefits.
 */
final class LoyaltyLevelChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $queue = 'notifications';

    /**
     * @param  string  $previousTier  The previous loyalty tier.
     * @param  string  $newTier  The new loyalty tier.
     * @param  int  $discountChange  The change in discount percentage.
     * @param  int  $totalPoints  The total loyalty points.
     * @param  bool  $isUpgrade  Whether this is an upgrade.
     */
    public function __construct(
        public readonly string $previousTier,
        public readonly string $newTier,
        public readonly int $discountChange,
        public readonly int $totalPoints,
        public readonly bool $isUpgrade
    ) {}

    /**
     * Determines the notification channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Builds the mail message.
     */
    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->isUpgrade ? 'Congratulations! You leveled up!' : 'Your loyalty level has changed')
            ->greeting('Hello!');

        if ($this->isUpgrade) {
            $message
                ->line("Congratulations! You've reached the {$this->newTier} tier!")
                ->line("Your discount has increased by {$this->discountChange}%")
                ->line("Total loyalty points: {$this->totalPoints}");
        } else {
            $message
                ->line("Your loyalty level has changed from {$this->previousTier} to {$this->newTier}.")
                ->line("Total loyalty points: {$this->totalPoints}");
        }

        return $message
            ->line('Thank you for your continued loyalty!')
            ->action('View Benefits', url('/loyalty'));
    }

    /**
     * Builds the database notification payload.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'previous_tier' => $this->previousTier,
            'new_tier' => $this->newTier,
            'discount_change' => $this->discountChange,
            'total_points' => $this->totalPoints,
            'is_upgrade' => $this->isUpgrade,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
