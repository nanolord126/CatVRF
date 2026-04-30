<?php

declare(strict_types=1);

namespace App\Domains\Audit\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

/**
 * ModelUpdatedEvent — Fired when a model is updated.
 * Triggers automatic audit logging with before/after values.
 */
final class ModelUpdatedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Model $model,
        public readonly array $oldValues,
        public readonly array $newValues,
        public readonly ?string $correlationId = null,
    ) {}
}
