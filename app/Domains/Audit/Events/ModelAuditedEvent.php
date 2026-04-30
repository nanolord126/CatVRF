<?php

declare(strict_types=1);

namespace App\Domains\Audit\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Domains\Audit\DTOs\CreateAuditLogDto;

/**
 * ModelAuditedEvent — Domain event fired when a model is audited.
 * This is the central event for all audit operations.
 */
final class ModelAuditedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly CreateAuditLogDto $auditLogDto,
    ) {}
}
