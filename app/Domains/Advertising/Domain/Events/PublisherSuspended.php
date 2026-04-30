<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Publisher Suspended Event
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final class PublisherSuspended
{
    use Dispatchable;

    public function __construct(
        public readonly int $publisherId,
        public readonly int $tenantId,
        public readonly string $reason,
        public readonly string $correlationId,
    ) {}
}
