<?php

declare(strict_types=1);

namespace App\Domains\Audit\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

/**
 * ModelDeletedEvent — Fired when a model is deleted.
 * Triggers automatic audit logging.
 */
final class ModelDeletedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Model $model,
        public readonly array $deletedValues,
        public readonly ?string $correlationId = null,
    ) {}
}
