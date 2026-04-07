<?php

declare(strict_types=1);

namespace App\Domains\Delivery\Interfaces\Notifications;

use App\Domains\Delivery\Domain\Entities\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Class DeliveryStatusNotification
 *
 * Part of the Delivery vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Delivery\Interfaces\Notifications
 */
final class DeliveryStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Delivery $delivery
    ) {

    }

    /**
     * Handle via operation.
     *
     * @throws \DomainException
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->line('The status of your delivery has been updated.')
            ->line('New status: ' . $this->delivery->status->value)
            ->action('View Delivery', url('/deliveries/' . $this->delivery->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'delivery_id' => $this->delivery->id,
            'status' => $this->delivery->status->value,
            'correlation_id' => $this->delivery->correlation_id,
        ];
    }
}
