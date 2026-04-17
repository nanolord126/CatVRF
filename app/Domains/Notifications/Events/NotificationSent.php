<?php declare(strict_types=1);

namespace App\Domains\Notifications\Events;

use App\Domains\Notifications\Models\Notification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class NotificationSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Notification $notification,
        public readonly string $correlationId,
    ) {}
}
