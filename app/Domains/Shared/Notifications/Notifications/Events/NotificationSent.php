<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Events;

use App\Domains\Shared\Notifications\Models\Notification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class NotificationSent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Notification $notification,
        public readonly string $correlationId,
    ) {}
}
