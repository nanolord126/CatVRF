<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Ad Short Created Event
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final class AdShortCreated
{
    use Dispatchable;

    public function __construct(
        public readonly int $adShortId,
        public readonly int $tenantId,
        public readonly string $correlationId,
    ) {}
}
