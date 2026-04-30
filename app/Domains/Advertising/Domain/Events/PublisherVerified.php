<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Publisher Verified Event
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final class PublisherVerified
{
    use Dispatchable;

    public function __construct(
        public readonly int $publisherId,
        public readonly int $tenantId,
        public readonly string $correlationId,
    ) {}
}
