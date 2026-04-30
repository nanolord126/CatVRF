<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

final readonly class SubscriptionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $message,
        private string $type,
        private array $meta = []
    ) {
    }

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(mixed $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'message' => $this->message,
            'type' => $this->type,
            'meta' => $this->meta,
            'created_at' => now()->toIso8601String()
        ]);
    }
}
