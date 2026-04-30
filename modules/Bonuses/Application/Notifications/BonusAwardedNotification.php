<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Bonuses\Domain\Enums\BonusType;
use DateTimeImmutable;

/**
 * Notification BonusAwardedNotification
 *
 * Notification sent to users when they receive a bonus.
 * Can be delivered via email, push notification, or in-app message.
 */
final class BonusAwardedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $queue = 'notifications';

    /**
     * @param  string  $bonusId  The bonus ID.
     * @param  int  $amount  The bonus amount in smallest currency unit.
     * @param  string  $type  The bonus type.
     * @param  DateTimeImmutable|null  $expiresAt  Optional expiration date.
     */
    public function __construct(
        public readonly string $bonusId,
        public readonly int $amount,
        public readonly string $type,
        public readonly ?DateTimeImmutable $expiresAt = null
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
        $amountFormatted = $this->amount / 100; // Convert to main currency unit

        return (new MailMessage)
            ->subject('You received a bonus!')
            ->greeting('Hello!')
            ->line("You have been awarded a bonus of {$amountFormatted}.")
            ->line("Bonus type: {$this->type}")
            ->when($this->expiresAt !== null, function ($message) {
                return $message->line("This bonus expires on: {$this->expiresAt->format('Y-m-d H:i:s')}");
            })
            ->line('Thank you for using our platform!')
            ->action('View Bonuses', url('/bonuses'));
    }

    /**
     * Builds the database notification payload.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'bonus_id' => $this->bonusId,
            'amount' => $this->amount,
            'type' => $this->type,
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
