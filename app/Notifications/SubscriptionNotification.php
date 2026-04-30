<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $message,
        public string $type,
        public array $meta = [],
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'message' => $this->message,
            'type' => $this->type,
            'meta' => $this->meta,
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'type' => $this->type,
            'meta' => $this->meta,
        ];
    }
}
