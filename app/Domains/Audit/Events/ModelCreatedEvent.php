<?php

declare(strict_types=1);

namespace App\Domains\Audit\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

/**
 * ModelCreatedEvent — Fired when a model is created.
 * Triggers automatic audit logging.
 */
final class ModelCreatedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Model $model,
        public readonly ?string $correlationId = null,
    ) {}
}
