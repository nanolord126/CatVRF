<?php declare(strict_types=1);

namespace App\Domains\Webhooks\Events;

use App\Domains\Webhooks\Models\Webhook;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WebhookTriggered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Webhook $webhook,
        public readonly string $eventType,
        public readonly string $correlationId,
    ) {}
}
